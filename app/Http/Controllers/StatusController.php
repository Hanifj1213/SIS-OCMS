<?php

namespace App\Http\Controllers;

use App\Models\Component;
use App\Models\OverhaulLog;
use App\Models\PartRequest;
use Carbon\Carbon;

/**
 * Endpoint JSON ringan untuk polling status realtime dari browser.
 * Semua method bersifat read-only.
 */
class StatusController extends Controller
{
    /**
     * Hitung metrik dashboard dengan query agregat (tanpa N+1).
     */
    public static function dashboardMetrics(): array
    {
        $onProgress   = Component::where('status', 'On Progress')->count();
        $readyForUse  = Component::where('status', 'Ready for Use')->count();
        $pendingParts = PartRequest::where('status', 'Pending')->count();

        $stageCounts = Component::whereIn('status', ['On Progress', 'Ready for Use'])
            ->selectRaw('current_stage, COUNT(*) as total')
            ->groupBy('current_stage')
            ->pluck('total', 'current_stage');

        $stageDistribution = [];
        for ($i = 1; $i <= 7; $i++) {
            $stageDistribution[$i] = (int) ($stageCounts[$i] ?? 0);
        }

        // Rata-rata lead time (jam) komponen RFU: satu query agregat per komponen
        $avgLeadTime = 0;
        $rfuIds = Component::where('status', 'Ready for Use')->pluck('comp_id');
        if ($rfuIds->isNotEmpty()) {
            $spans = OverhaulLog::whereIn('comp_id', $rfuIds)
                ->selectRaw('comp_id, MIN(start_time) as first_start, MAX(end_time) as last_end')
                ->groupBy('comp_id')
                ->get();

            $totalHours = 0;
            $counted = 0;
            foreach ($spans as $span) {
                if ($span->first_start && $span->last_end) {
                    $totalHours += Carbon::parse($span->first_start)->diffInHours(Carbon::parse($span->last_end));
                    $counted++;
                }
            }
            $avgLeadTime = $counted > 0 ? round($totalHours / $counted, 1) : 0;
        }

        return compact('onProgress', 'readyForUse', 'avgLeadTime', 'stageDistribution', 'pendingParts');
    }

    public function dashboard()
    {
        return response()->json(self::dashboardMetrics());
    }

    /**
     * Status ringkas semua komponen (untuk halaman daftar komponen).
     */
    public function components()
    {
        $components = Component::latest()
            ->get(['comp_id', 'current_stage', 'status', 'status_ovh', 'is_waiting_approval']);

        return response()->json([
            'count' => $components->count(),
            'components' => $components,
        ]);
    }

    /**
     * Fingerprint status satu komponen (untuk halaman detail).
     * Checksheet answers sengaja TIDAK ikut agar auto-refresh tidak
     * mengganggu user yang sedang mengisi checksheet di halaman yang sama.
     */
    public function component(Component $component)
    {
        $state = [
            'current_stage' => $component->current_stage,
            'status' => $component->status,
            'is_waiting_approval' => (bool) $component->is_waiting_approval,
            'part_requests' => $component->partRequests()->pluck('status', 'req_id'),
            'inspections' => $component->inspectionDetails()->count(),
            'logs' => $component->overhaulLogs()->count(),
        ];

        return response()->json([
            'fingerprint' => md5(json_encode($state)),
            'current_stage' => $component->current_stage,
            'status' => $component->status,
            'is_waiting_approval' => (bool) $component->is_waiting_approval,
        ]);
    }

    /**
     * Status semua part request (untuk halaman gudang).
     */
    public function partRequests()
    {
        $requests = PartRequest::latest()->get(['req_id', 'status']);

        return response()->json([
            'count' => $requests->count(),
            'requests' => $requests,
        ]);
    }
}
