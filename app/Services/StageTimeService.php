<?php

namespace App\Services;

use App\Models\Component;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Perhitungan waktu 3 dimensi per tahap overhaul:
 *
 * - Calendar Hour : waktu absolut 24/7 (end - start).
 * - Work Hour     : Calendar Hour dipotong jam tutup bengkel
 *                   (config/worktime.php, jam operasional 07:30-16:30).
 * - Man Hour      : akumulasi jam hadir tiap mekanik (satu baris
 *                   stage_mechanic_logs per orang), dipotong jam tutup
 *                   bengkel DAN jam istirahat (Work Hour tidak dipotong
 *                   istirahat, Man Hour iya).
 */
class StageTimeService
{
    public function calendarSeconds(CarbonInterface $start, ?CarbonInterface $end = null): int
    {
        $end = $end ?? Carbon::now();

        return max(0, (int) $start->diffInSeconds($end, false));
    }

    /**
     * Work Hour: detik efektif antara $start dan $end setelah memotong
     * jendela tutup bengkel yang berulang setiap hari.
     *
     * Dihitung dengan rumus tertutup O(1) per jendela — bukan loop hari
     * per hari — supaya rentang ekstrem (mis. salah input tahun 0089)
     * tidak membuat request timeout.
     */
    public function workSeconds(CarbonInterface $start, ?CarbonInterface $end = null): int
    {
        $end = $end ?? Carbon::now();
        if ($end->lessThanOrEqualTo($start)) {
            return 0;
        }

        $total = (int) $start->diffInSeconds($end, false);

        foreach (config('worktime.off_windows', []) as $window) {
            $total -= $this->overlapWithDailyWindow($start, $end, $window['start'], $window['end']);
        }

        return max(0, $total);
    }

    /**
     * Total detik overlap [start, end) dengan jendela harian [ws, we).
     */
    private function overlapWithDailyWindow(CarbonInterface $start, CarbonInterface $end, string $windowStart, string $windowEnd): int
    {
        $ws = $this->timeStringToSeconds($windowStart);
        $we = $this->timeStringToSeconds($windowEnd);
        if ($we <= $ws) {
            return 0;
        }

        $startSod = $start->secondsSinceMidnight();
        $endSod = $end->secondsSinceMidnight();
        // Jumlah pergantian hari antara start dan end
        $dayspan = (int) $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay(), false);

        if ($dayspan === 0) {
            return max(0, min($endSod, $we) - max($startSod, $ws));
        }

        $firstDay = max(0, $we - max($startSod, $ws));
        $lastDay = max(0, min($endSod, $we) - $ws);
        $fullDays = max(0, $dayspan - 1);

        return $firstDay + $lastDay + $fullDays * ($we - $ws);
    }

    private function timeStringToSeconds(string $time): int
    {
        [$h, $m] = explode(':', $time);

        return ((int) $h) * 3600 + ((int) $m) * 60;
    }

    /**
     * Detik kerja efektif SATU mekanik antara $start dan $end:
     * dipotong jam tutup bengkel dan jam istirahat harian.
     */
    public function manWindowSeconds(CarbonInterface $start, ?CarbonInterface $end = null): int
    {
        $end = $end ?? Carbon::now();
        if ($end->lessThanOrEqualTo($start)) {
            return 0;
        }

        $total = $this->workSeconds($start, $end);

        // Istirahat berada di dalam jam buka, jadi aman dikurangkan langsung
        foreach (config('worktime.breaks', []) as $window) {
            $total -= $this->overlapWithDailyWindow($start, $end, $window['start'], $window['end']);
        }

        return max(0, $total);
    }

    /**
     * Man Hour: akumulasi jam hadir seluruh mekanik pada tahap tersebut.
     * Satu baris log = satu orang; baris yang masih aktif dihitung sampai
     * sekarang (atau sampai tahap selesai).
     */
    public function manSeconds(Component $component, int $stageNumber, ?CarbonInterface $stageEnd = null): int
    {
        $total = 0;
        $logs = $component->mechanicLogs->where('stage_number', $stageNumber);

        foreach ($logs as $log) {
            $sessionEnd = $log->clock_out ?? $stageEnd ?? Carbon::now();
            $total += $this->manWindowSeconds($log->clock_in, $sessionEnd) * max(1, (int) $log->crew_count);
        }

        return $total;
    }

    /**
     * Metrik lengkap semua tahap untuk satu komponen — dipakai halaman detail
     * dan endpoint polling live timer.
     */
    public function metricsFor(Component $component): array
    {
        $component->loadMissing(['overhaulLogs', 'mechanicLogs.mechanic']);

        $metrics = [];
        foreach ($component->overhaulLogs as $log) {
            if (!$log->start_time) {
                continue;
            }

            $start = Carbon::parse($log->start_time);
            $end = $log->end_time ? Carbon::parse($log->end_time) : null;
            $stage = (int) $log->stage_number;

            $crewLogs = $component->mechanicLogs->where('stage_number', $stage);
            $activeCrew = $crewLogs->whereNull('clock_out');

            $metrics[$stage] = [
                'stage' => $stage,
                'running' => $end === null,
                'calendar_seconds' => $this->calendarSeconds($start, $end),
                'work_seconds' => $this->workSeconds($start, $end),
                'man_seconds' => $this->manSeconds($component, $stage, $end),
                // Jumlah mekanik yang sedang bekerja = jumlah nama aktif
                'active_crew' => (int) $activeCrew->sum('crew_count'),
                // Hanya crew AKTIF yang ditampilkan (daftar chip nama)
                'crew' => $activeCrew->sortBy('clock_in')->map(fn ($c) => [
                    'log_id' => $c->id,
                    'name' => $c->crew_names ?: 'Mekanik',
                    'since' => $c->clock_in?->format('d/m H:i'),
                ])->values()->all(),
            ];
        }

        return $metrics;
    }

    /**
     * Format detik → "12j 05m" untuk tampilan.
     */
    public static function formatHours(int $seconds): string
    {
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        return sprintf('%dj %02dm', $hours, $minutes);
    }
}
