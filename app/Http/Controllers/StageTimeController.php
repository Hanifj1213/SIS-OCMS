<?php

namespace App\Http\Controllers;

use App\Models\Component;
use App\Models\StageMechanicLog;
use App\Services\StageTimeService;
use Illuminate\Http\Request;

/**
 * Pelacakan waktu 3 dimensi per tahap (Calendar / Work / Man Hour).
 *
 * Man Hour digerakkan daftar nama crew aktif: tambah nama = multiplier
 * naik sejak saat itu, hapus nama = berhenti. Tidak ada tombol start/stop —
 * jam kerja (07:30-16:30) dan jam istirahat dipotong otomatis dari config.
 * Setiap nama tercatat kapan masuk & keluar sehingga Man Hour tetap akurat.
 */
class StageTimeController extends Controller
{
    /**
     * Metrik live seluruh tahap — dipanggil polling JS di halaman detail.
     */
    public function metrics(Component $component, StageTimeService $service)
    {
        return response()->json([
            'server_time' => now()->toIso8601String(),
            'off_windows' => config('worktime.off_windows', []),
            'breaks' => config('worktime.breaks', []),
            'stages' => $service->metricsFor($component),
        ]);
    }

    /**
     * Tambah satu mekanik (nama bebas) ke crew tahap yang sedang berjalan.
     */
    public function addMechanic(Request $request, Component $component)
    {
        if (!auth()->user()->hasAnyRole(['Mechanic', 'Supervisor', 'SuperAdmin'])) {
            return back()->withErrors(['crew' => 'Anda tidak memiliki izin mengubah crew.']);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
        ], [], ['name' => 'nama mekanik']);

        $name = trim($validated['name']);
        $stage = $component->current_stage;

        $duplicate = $component->mechanicLogs()
            ->where('stage_number', $stage)
            ->whereNull('clock_out')
            ->get()
            ->contains(fn ($log) => mb_strtolower(trim((string) $log->crew_names)) === mb_strtolower($name));

        if ($duplicate) {
            return back()->withErrors(['crew' => '"' . $name . '" sudah ada di crew tahap ini.']);
        }

        $component->mechanicLogs()->create([
            'stage_number' => $stage,
            'user_id' => auth()->id(),
            'crew_count' => 1,
            'crew_names' => $name,
            'clock_in' => now(),
        ]);

        return back()->with('success', $name . ' ditambahkan ke crew — Man Hour ikut menghitung mulai sekarang.');
    }

    /**
     * Hapus mekanik dari crew: jam hadirnya ditutup (bukan dihapus dari
     * riwayat) supaya Man Hour yang sudah berjalan tetap tercatat.
     */
    public function removeMechanic(Component $component, StageMechanicLog $log)
    {
        if (!auth()->user()->hasAnyRole(['Mechanic', 'Supervisor', 'SuperAdmin'])) {
            return back()->withErrors(['crew' => 'Anda tidak memiliki izin mengubah crew.']);
        }

        if ($log->comp_id !== $component->comp_id) {
            abort(404);
        }

        if ($log->clock_out) {
            return back()->withErrors(['crew' => 'Mekanik ini sudah tidak aktif.']);
        }

        $log->update(['clock_out' => now()]);

        return back()->with('success', ($log->crew_names ?: 'Mekanik') . ' dikeluarkan dari crew.');
    }
}
