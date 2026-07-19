<?php

namespace App\Http\Controllers;

use App\Models\Component;
use App\Models\StageMechanicLog;
use App\Services\StageCrewIntegrityService;
use App\Services\StageTimeService;
use Illuminate\Http\Request;

class StageTimeController extends Controller
{
    public function __construct(
        private readonly StageCrewIntegrityService $crewIntegrity,
    ) {}

    public function metrics(Component $component, StageTimeService $service)
    {
        return response()->json([
            'server_time' => now()->toIso8601String(),
            'off_windows' => config('worktime.off_windows', []),
            'breaks' => config('worktime.breaks', []),
            'stages' => $service->metricsFor($component),
        ]);
    }

    public function addMechanic(Request $request, Component $component)
    {
        if (! auth()->user()?->canOperateOverhaul()) {
            return back()->withErrors(['crew' => 'Anda tidak memiliki izin mengubah crew.']);
        }

        $component = $component->fresh();

        if ($denied = $this->crewIntegrity->mutationDeniedReason($component)) {
            return back()->withErrors(['crew' => $denied]);
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
            return back()->withErrors(['crew' => '"'.$name.'" sudah ada di crew tahap ini.']);
        }

        $component->mechanicLogs()->create([
            'stage_number' => $stage,
            'user_id' => auth()->id(),
            'crew_count' => 1,
            'crew_names' => $name,
            'clock_in' => now(),
        ]);

        return back()->with('success', $name.' ditambahkan ke crew — Man Hour ikut menghitung mulai sekarang.');
    }

    public function removeMechanic(Component $component, StageMechanicLog $log)
    {
        if (! auth()->user()?->canOperateOverhaul()) {
            return back()->withErrors(['crew' => 'Anda tidak memiliki izin mengubah crew.']);
        }

        if ($log->comp_id !== $component->comp_id) {
            abort(404);
        }

        if ($denied = $this->crewIntegrity->mutationDeniedReason($component->fresh(), $log)) {
            return back()->withErrors(['crew' => $denied]);
        }

        $log->update(['clock_out' => now()]);

        return back()->with('success', ($log->crew_names ?: 'Mekanik').' dikeluarkan dari crew.');
    }
}
