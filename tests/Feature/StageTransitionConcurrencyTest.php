<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\StageMechanicLog;
use App\Models\User;
use App\Services\StageTransitionService;
use Database\Seeders\DeliveryChecksheetTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * P1.1 — Transisi tahap atomik & aman terhadap konkurensi.
 *
 * Dua request bersamaan (updateStage / approveStage) tidak boleh
 * menghasilkan dua transisi, FR/PR/log ganda, atau crew yang tetap
 * aktif setelah tahap selesai. Seluruh mutasi di dalam satu transaksi
 * dengan lockForUpdate() pada komponen.
 */
class StageTransitionConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    private User $mechanic;
    private User $groupLeader;
    private Component $component;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();

        Role::findOrCreate('Mechanic', 'web');
        Role::findOrCreate('Group Leader', 'web');

        $this->mechanic = User::create([
            'name' => 'Mekanik Konkuren',
            'nik' => 'KONK-MEC-' . random_int(1000, 9999),
            'password' => 'password',
        ]);
        $this->mechanic->assignRole('Mechanic');

        $this->groupLeader = User::create([
            'name' => 'Group Leader Konkuren',
            'nik' => 'KONK-GL-' . random_int(1000, 9999),
            'password' => 'password',
        ]);
        $this->groupLeader->assignRole('Group Leader');

        $this->component = Component::create([
            'serial_number' => 'KONK-' . random_int(10000, 99999),
            'egi' => 'SA12V140E-1',
            'model_type' => 'SA12V140E-1',
            'major_category' => 'Engine',
            'current_stage' => 1,
            'status' => 'On Progress',
        ]);
        $this->component->overhaulLogs()->create([
            'stage_number' => 1,
            'mechanic_id' => $this->mechanic->id,
            'start_time' => now(),
            'notes' => 'Komponen diterima di PRC (Receiving)',
        ]);
    }

    public function test_dua_pengajuan_approval_bersamaan_hanya_menghasilkan_satu(): void
    {
        // Stage 1 → 2 tanpa approval, kemudian stage 2 butuh approval.
        // Simulasikan dua mekanik mengajukan pada saat yang sama.
        $component = $this->component;
        $component->update(['current_stage' => 2]);
        $component->overhaulLogs()->create([
            'stage_number' => 2,
            'mechanic_id' => $this->mechanic->id,
            'start_time' => now(),
        ]);

        $service = app(StageTransitionService::class);

        $results = DB::transaction(function () use ($service, $component) {
            // Dua pengajuan "bersamaan" — keduanya memakai state awal yang sama
            $locked = $service->lockComponent($component);
            $first = $service->requestApproval($locked, $this->mechanic->id);

            // Simulasi request kedua yang sudah punya model stale
            $stale = Component::find($component->comp_id);
            $second = $service->requestApproval($stale, $this->mechanic->id);

            return [$first, $second];
        });

        $this->assertTrue($results[0]);
        $this->assertFalse($results[1]);

        $component->refresh();
        $this->assertTrue($component->is_waiting_approval);

        // Hanya satu log stage 2 yang mencatat pengajuan
        $log = $component->overhaulLogs()->where('stage_number', 2)->first();
        $this->assertNotNull($log->approval_requested_at);
        $this->assertSame(1, $component->overhaulLogs()->where('stage_number', 2)->count());
    }

    public function test_dua_approval_bersamaan_hanya_menghasilkan_satu_transisi(): void
    {
        $component = $this->component;
        $component->update(['current_stage' => 2, 'is_waiting_approval' => true]);
        $component->overhaulLogs()->create([
            'stage_number' => 2,
            'mechanic_id' => $this->mechanic->id,
            'start_time' => now(),
            'approval_requested_by' => $this->mechanic->id,
            'approval_requested_at' => now(),
        ]);

        $service = app(StageTransitionService::class);

        $first = $service->inTransaction(function () use ($service, $component) {
            $locked = $service->lockComponent($component);
            return $service->advance($locked, $this->groupLeader->id, approvedBy: $this->groupLeader->id);
        });

        // Request kedua memakai model stale — harus ditolak invariant
        $stale = Component::find($component->comp_id);
        $second = $service->inTransaction(function () use ($service, $stale) {
            $locked = $service->lockComponent($stale);
            return $service->advance($locked, $this->groupLeader->id, approvedBy: $this->groupLeader->id);
        });

        $this->assertFalse($first); // stage 3, bukan final
        $this->assertFalse($second); // ditolak: tidak sedang menunggu approval

        $component->refresh();
        $this->assertSame(3, $component->current_stage);
        $this->assertFalse($component->is_waiting_approval);

        // Log stage 2 tertutup sekali, log stage 3 hanya satu
        $this->assertSame(1, $component->overhaulLogs()->where('stage_number', 2)->whereNotNull('end_time')->count());
        $this->assertSame(1, $component->overhaulLogs()->where('stage_number', 3)->count());
    }

    public function test_exception_di_tengah_write_merollback_seluruh_perubahan(): void
    {
        $component = $this->component;
        $component->update(['current_stage' => 2, 'is_waiting_approval' => true]);
        $component->overhaulLogs()->create([
            'stage_number' => 2,
            'mechanic_id' => $this->mechanic->id,
            'start_time' => now(),
            'approval_requested_by' => $this->mechanic->id,
            'approval_requested_at' => now(),
        ]);

        $service = app(StageTransitionService::class);

        $beforeLogs = $component->overhaulLogs()->count();
        $beforeStage = $component->fresh()->current_stage;
        $beforeWaiting = $component->fresh()->is_waiting_approval;

        try {
            $service->inTransaction(function () use ($service, $component) {
                $locked = $service->lockComponent($component);
                $service->advance($locked, $this->groupLeader->id, approvedBy: $this->groupLeader->id);
                // Gagal setelah mutasi — harus rollback
                throw new \RuntimeException('Simulasi kegagalan write.');
            });
            $this->fail('Harusnya melempar exception.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Simulasi kegagalan write.', $e->getMessage());
        }

        $component->refresh();
        $this->assertSame($beforeStage, $component->current_stage);
        $this->assertSame($beforeWaiting, $component->is_waiting_approval);
        $this->assertSame($beforeLogs, $component->overhaulLogs()->count());
    }

    public function test_crew_aktif_ditutup_saat_transisi_dan_man_hour_berhenti(): void
    {
        $this->seed(DeliveryChecksheetTemplateSeeder::class);

        $component = $this->component;
        $component->update(['current_stage' => 6]);
        $component->overhaulLogs()->create([
            'stage_number' => 6,
            'mechanic_id' => $this->mechanic->id,
            'start_time' => now()->subDay(),
        ]);
        // Di alur nyata checksheet stage 6 dibuat advance() saat masuk stage;
        // di test kita masuk langsung, jadi generate manual.
        app(StageTransitionService::class)->ensureChecksheetForStage($component, 6);
        StageMechanicLog::create([
            'comp_id' => $component->comp_id,
            'stage_number' => 6,
            'user_id' => $this->mechanic->id,
            'crew_count' => 1,
            'crew_names' => 'Budi',
            'clock_in' => now()->subHour(),
        ]);

        $this->actingAs($this->mechanic)
            ->post(route('components.updateStage', $component->comp_id))
            ->assertSessionHasErrors('stage'); // checksheet stage 6 belum lengkap

        // Isi checksheet
        $checksheet = $component->checksheets()->where('stage_number', 6)->first();
        $answers = collect($checksheet->items)->mapWithKeys(fn ($item) => [$item['id'] => 'good'])->all();
        $checksheet->update(['answers' => $answers, 'completed_at' => now()]);

        $this->actingAs($this->mechanic)
            ->post(route('components.updateStage', $component->comp_id))
            ->assertSessionHasNoErrors();

        $component->refresh();
        $this->assertSame(7, $component->current_stage);

        // Crew tidak boleh tetap aktif setelah transisi
        $activeCrew = StageMechanicLog::where('comp_id', $component->comp_id)
            ->where('stage_number', 6)
            ->whereNull('clock_out')
            ->count();
        $this->assertSame(0, $activeCrew);

        // Man Hour tidak bertambah setelah stage selesai
        $crew = StageMechanicLog::where('comp_id', $component->comp_id)->where('stage_number', 6)->first();
        $this->assertNotNull($crew->clock_out);
        $this->assertLessThanOrEqual($crew->clock_out->timestamp, now()->timestamp);
    }
}
