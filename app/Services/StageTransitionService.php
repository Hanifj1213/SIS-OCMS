<?php

namespace App\Services;

use App\Http\Controllers\ComponentController;
use App\Models\ChecksheetTemplate;
use App\Models\Component;
use App\Models\ComponentChecksheet;
use Illuminate\Support\Facades\DB;

/**
 * Logika perpindahan tahap overhaul (stage 1-7): tutup log berjalan,
 * naikkan stage, snapshot checksheet dari template, buat log baru.
 *
 * Semua mutasi status tahap berjalan di dalam SATU transaksi database
 * dengan komponen dikunci lewat lockForUpdate() supaya dua request
 * bersamaan tidak bisa memproses tahap yang sama dua kali:
 *
 * - dua pengajuan approval hanya menghasilkan satu pengajuan;
 * - dua approval hanya menghasilkan satu transisi;
 * - exception di tengah penulisan merollback seluruh perubahan.
 *
 * Side effect eksternal (dispatch queue GSheet) WAJIB dilakukan di luar
 * transaksi oleh pemanggil (afterCommit / setelah commit).
 */
class StageTransitionService
{
    /**
     * Jumlah percobaan saat transaksi gagal karena deadlock/unique collision.
     * Terbatas dan terukur — bukan retry tanpa batas.
     */
    private const MAX_RETRIES = 3;

    /**
     * Snapshot checksheet dari template saat komponen masuk sebuah stage.
     * Template EGI-spesifik menang; fallback ke Generic (egi_model null).
     * Jawaban yang sudah ada tidak pernah ditimpa — snapshot adalah rekam
     * audit checklist yang dipakai komponen itu.
     */
    public function ensureChecksheetForStage(Component $component, int $stage): void
    {
        if ($component->checksheets()->where('stage_number', $stage)->exists()) {
            return;
        }

        $egi = strtoupper(trim((string) $component->egi));
        $template = ChecksheetTemplate::query()
            ->where('major_category', $component->major_category)
            ->where('stage_number', $stage)
            ->whereRaw('UPPER(egi_model) = ?', [$egi])
            ->first();

        $template ??= ChecksheetTemplate::query()
            ->where('major_category', $component->major_category)
            ->where('stage_number', $stage)
            ->whereNull('egi_model')
            ->first();

        if (!$template) {
            return;
        }

        ComponentChecksheet::create([
            'comp_id' => $component->comp_id,
            'stage_number' => $stage,
            'items' => $template->items,
            'answers' => [],
        ]);
    }

    /**
     * Tandai komponen menunggu approval GL/Supervisor dan catat pengajunya.
     *
     * Jalankan dari dalam transaksi StageTransitionService::inTransaction()
     * dengan komponen terkunci. Mengembalikan false bila komponen ternyata
     * sudah menunggu approval (duplicate submit) atau sudah melewati tahap.
     */
    public function requestApproval(Component $component, int $requestedBy): bool
    {
        $locked = $this->lockComponent($component);

        if ($locked->is_waiting_approval) {
            return false;
        }

        $locked->update(['is_waiting_approval' => true]);

        $locked->overhaulLogs()
            ->where('stage_number', $locked->current_stage)
            ->latest('log_id')
            ->first()
            ?->update([
                'approval_requested_by' => $requestedBy,
                'approval_requested_at' => now(),
                'approved_by' => null,
                'approved_at' => null,
            ]);

        return true;
    }

    /**
     * Naikkan komponen satu tahap: tutup log berjalan, update status,
     * snapshot checksheet stage baru, dan buat log stage baru.
     *
     * @param  int|null  $approvedBy  ID atasan yang approve (null = auto-transition tanpa approval)
     * @return bool true bila komponen mencapai tahap akhir (RFU)
     */
    public function advance(Component $component, int $actorId, ?int $approvedBy = null): bool
    {
        $locked = $this->lockComponent($component);

        if ($approvedBy !== null && ! $locked->is_waiting_approval) {
            return false; // Approval ganda / tidak sedang menunggu
        }

        $currentStage = $locked->current_stage;
        $nextStage = $currentStage + 1;

        // Tutup log tahapan saat ini (+ jejak approval bila lewat jalur approve)
        $currentLog = $locked->overhaulLogs()
            ->where('stage_number', $currentStage)
            ->latest('log_id')
            ->first();

        if ($currentLog) {
            $update = ['end_time' => now()];
            if ($approvedBy !== null) {
                $update['approved_by'] = $approvedBy;
                $update['approved_at'] = now();
            }
            $currentLog->update($update);
        }

        // Crew aktif ditutup pada timestamp yang sama dengan penutupan log
        // tahap, supaya Man Hour tidak bertambah setelah stage selesai.
        $now = now();
        $locked->mechanicLogs()
            ->where('stage_number', $currentStage)
            ->whereNull('clock_out')
            ->update(['clock_out' => $now]);

        $isFinalCompleted = ($nextStage == 7);
        $locked->update([
            'current_stage' => $nextStage,
            'is_waiting_approval' => false,
            'status' => $isFinalCompleted ? 'Ready for Use' : 'On Progress',
        ]);

        $this->ensureChecksheetForStage($locked, $nextStage);

        $suffix = $approvedBy !== null ? ' (Approved)' : '';
        $stageNote = ComponentController::STAGE_NAMES[$nextStage] ?? 'Tahap ' . $nextStage;
        $logData = [
            'stage_number' => $nextStage,
            'mechanic_id' => $actorId,
            'start_time' => $now,
            'notes' => 'Memulai: ' . $stageNote . $suffix,
        ];

        // Jika sudah tahap akhir (RFU), langsung tutup lognya
        if ($isFinalCompleted) {
            $logData['end_time'] = $now;
            $logData['notes'] = 'Seluruh tahapan overhaul selesai — Komponen Ready for Use (RFU)' . $suffix;
        }

        $locked->overhaulLogs()->create($logData);

        return $isFinalCompleted;
    }

    /**
     * Tolak pengajuan approval: komponen kembali ke mekanik, jejak
     * pengajuan direset supaya bisa diajukan ulang.
     */
    public function reject(Component $component, string $rejectorName): void
    {
        $locked = $this->lockComponent($component);

        if (! $locked->is_waiting_approval) {
            return; // Duplicate reject / sudah tidak menunggu
        }

        $locked->update(['is_waiting_approval' => false]);

        $rejectLog = $locked->overhaulLogs()
            ->where('stage_number', $locked->current_stage)
            ->latest('log_id')
            ->first();

        if ($rejectLog) {
            $rejectLog->update([
                'notes' => trim(($rejectLog->notes ?? '') . "\n\nApproval ditolak oleh " . $rejectorName . ' (' . now()->format('d/m/Y H:i') . ')'),
                'approval_requested_by' => null,
                'approval_requested_at' => null,
            ]);
        }
    }

    /**
     * Jalankan callback dalam transaksi dengan retry terbatas.
     *
     * @template T
     * @param  callable(): T  $callback
     * @return T
     */
    public function inTransaction(callable $callback): mixed
    {
        return DB::transaction($callback, self::MAX_RETRIES);
    }

    /**
     * Muat ulang komponen dengan FOR UPDATE di dalam transaksi. Semua
     * prasyarat dan mutasi tahap WAJIB memakai instance terkunci ini agar
     * request yang memakai model stale tidak dapat melewati invariant.
     */
    public function lockComponent(Component $component): Component
    {
        /** @var Component|null $locked */
        $locked = Component::query()
            ->whereKey($component->comp_id)
            ->lockForUpdate()
            ->first();

        if ($locked === null) {
            abort(404, 'Komponen tidak ditemukan.');
        }

        return $locked;
    }
}
