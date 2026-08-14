<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\StageMechanicLog;
use App\Models\User;
use App\Services\StageTimeService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Waktu 3 Dimensi per tahap:
 * - Calendar Hour = end - start (24/7)
 * - Work Hour    = Calendar Hour dipotong jam tutup bengkel (operasional 07:30-16:30)
 * - Man Hour     = akumulasi (work hour segmen x jumlah crew); PIC mencatat
 *                  JUMLAH mekanik yang bekerja, bukan akun per orang.
 */
class StageTimeTrackingTest extends TestCase
{
    use RefreshDatabase;

    private User $mechanic;
    private User $supervisor;
    private Component $component;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('Mechanic', 'web');
        Role::findOrCreate('Supervisor', 'web');

        $this->mechanic = User::create([
            'name' => 'PIC Timer',
            'nik' => 'TMR-' . random_int(1000, 9999),
            'password' => 'password',
        ]);
        $this->mechanic->assignRole('Mechanic');

        $this->supervisor = User::create([
            'name' => 'Supervisor Timer',
            'nik' => 'SPV-' . random_int(1000, 9999),
            'password' => 'password',
        ]);
        $this->supervisor->assignRole('Supervisor');

        $this->component = Component::create([
            'serial_number' => 'SN-TIME-' . random_int(1000, 9999),
            'major_category' => 'Engine',
            'model_type' => 'Engine',
            'current_stage' => 2,
            'status' => 'On Progress',
        ]);
        $this->component->overhaulLogs()->create([
            'stage_number' => 2,
            'start_time' => now()->subHours(3),
        ]);
    }

    public function test_calendar_hour_adalah_selisih_absolut(): void
    {
        $service = new StageTimeService();
        $start = Carbon::parse('2026-08-05 08:00:00');
        $end = Carbon::parse('2026-08-06 08:00:00');

        $this->assertSame(24 * 3600, $service->calendarSeconds($start, $end));
    }

    public function test_work_hour_memotong_jam_tutup_bengkel(): void
    {
        $service = new StageTimeService();

        // Malam hari sepenuhnya di luar jam buka: 0 jam kerja
        $start = Carbon::parse('2026-08-05 22:00:00');
        $end = Carbon::parse('2026-08-06 06:00:00');
        $this->assertSame(0, $service->workSeconds($start, $end));

        // Rentang sepenuhnya di jam buka: tidak terpotong
        $start = Carbon::parse('2026-08-05 08:00:00');
        $end = Carbon::parse('2026-08-05 12:00:00');
        $this->assertSame(4 * 3600, $service->workSeconds($start, $end));

        // 08:00 → 17:00: terpotong jam tutup 16:30 => 8,5 jam kerja
        $start = Carbon::parse('2026-08-05 08:00:00');
        $end = Carbon::parse('2026-08-05 17:00:00');
        $this->assertSame((int) (8.5 * 3600), $service->workSeconds($start, $end));

        // 24 jam penuh = 9 jam kerja (bengkel buka 07:30-16:30)
        $start = Carbon::parse('2026-08-05 06:00:00');
        $end = Carbon::parse('2026-08-06 06:00:00');
        $this->assertSame(9 * 3600, $service->workSeconds($start, $end));
    }

    public function test_man_hour_berhenti_saat_istirahat_tapi_work_hour_lanjut(): void
    {
        $service = new StageTimeService();

        // 09:00 → 13:00 = 4 jam kalender, seluruhnya di jam buka.
        // Work Hour: tidak dipotong istirahat = 4 jam.
        $start = Carbon::parse('2026-08-05 09:00:00');
        $end = Carbon::parse('2026-08-05 13:00:00');
        $this->assertSame(4 * 3600, $service->workSeconds($start, $end));

        // Man Hour per orang: dipotong istirahat 09:45-10:00 (15m)
        // dan 11:30-12:30 (1 jam) => 2 jam 45 menit.
        $this->assertSame((int) (2.75 * 3600), $service->manWindowSeconds($start, $end));
    }

    public function test_man_hour_akumulasi_per_nama_mekanik(): void
    {
        $service = new StageTimeService();

        // Budi hadir 08:00-09:00 (1 jam, tanpa istirahat) = 1 man hour
        StageMechanicLog::create([
            'comp_id' => $this->component->comp_id,
            'stage_number' => 2,
            'user_id' => $this->mechanic->id,
            'crew_count' => 1,
            'crew_names' => 'Budi',
            'clock_in' => Carbon::parse('2026-08-05 08:00:00'),
            'clock_out' => Carbon::parse('2026-08-05 09:00:00'),
        ]);
        // Andi hadir 08:00-10:30 => 2,5 jam - istirahat 15m = 2,25 man hour
        StageMechanicLog::create([
            'comp_id' => $this->component->comp_id,
            'stage_number' => 2,
            'user_id' => $this->mechanic->id,
            'crew_count' => 1,
            'crew_names' => 'Andi',
            'clock_in' => Carbon::parse('2026-08-05 08:00:00'),
            'clock_out' => Carbon::parse('2026-08-05 10:30:00'),
        ]);

        $this->component->unsetRelation('mechanicLogs');
        $this->assertSame((int) (3.25 * 3600), $service->manSeconds($this->component, 2));
    }

    public function test_tambah_dan_hapus_nama_mekanik_dari_crew(): void
    {
        // Tambah dua nama
        $this->actingAs($this->mechanic)->post(
            route('components.crew.add', $this->component->comp_id),
            ['name' => 'Budi']
        )->assertSessionHasNoErrors();

        $this->actingAs($this->mechanic)->post(
            route('components.crew.add', $this->component->comp_id),
            ['name' => 'Andi']
        )->assertSessionHasNoErrors();

        $active = StageMechanicLog::where('comp_id', $this->component->comp_id)->whereNull('clock_out')->get();
        $this->assertCount(2, $active);

        // Nama sama (beda kapital) ditolak selama masih aktif
        $this->actingAs($this->mechanic)->post(
            route('components.crew.add', $this->component->comp_id),
            ['name' => 'budi']
        )->assertSessionHasErrors('crew');

        // Hapus Budi: baris ditutup (bukan dihapus) supaya Man Hour tersimpan
        $budi = $active->firstWhere('crew_names', 'Budi');
        $this->actingAs($this->mechanic)->delete(
            route('components.crew.remove', [$this->component->comp_id, $budi->id])
        )->assertSessionHasNoErrors();

        $this->assertNotNull($budi->fresh()->clock_out);
        $this->assertSame(1, StageMechanicLog::where('comp_id', $this->component->comp_id)->whereNull('clock_out')->count());

        // Setelah tidak aktif, nama yang sama boleh masuk lagi (besoknya dst.)
        $this->actingAs($this->mechanic)->post(
            route('components.crew.add', $this->component->comp_id),
            ['name' => 'Budi']
        )->assertSessionHasNoErrors();
    }

    public function test_endpoint_metrik_mengembalikan_tiga_dimensi_waktu(): void
    {
        foreach (['Budi', 'Andi', 'Slamet'] as $nm) {
            StageMechanicLog::create([
                'comp_id' => $this->component->comp_id,
                'stage_number' => 2,
                'user_id' => $this->mechanic->id,
                'crew_count' => 1,
                'crew_names' => $nm,
                'clock_in' => now()->subHour(),
            ]);
        }

        $response = $this->actingAs($this->mechanic)->getJson(
            route('components.timeMetrics', $this->component->comp_id)
        );

        $response->assertOk()
            ->assertJsonStructure([
                'server_time',
                'off_windows',
                'breaks',
                'stages' => [
                    2 => ['stage', 'running', 'calendar_seconds', 'work_seconds', 'man_seconds', 'active_crew', 'crew'],
                ],
            ]);

        $stage = $response->json('stages.2');
        $this->assertTrue($stage['running']);
        $this->assertSame(3, $stage['active_crew']);
        $this->assertSame(['Budi', 'Andi', 'Slamet'], array_column($stage['crew'], 'name'));
        $this->assertGreaterThan(0, $stage['calendar_seconds']);
        $this->assertGreaterThanOrEqual($stage['work_seconds'], $stage['calendar_seconds']);
    }

    public function test_halaman_detail_menampilkan_kartu_metrik_dan_chip_crew(): void
    {
        StageMechanicLog::create([
            'comp_id' => $this->component->comp_id,
            'stage_number' => 2,
            'user_id' => $this->mechanic->id,
            'crew_count' => 1,
            'crew_names' => 'Budi',
            'clock_in' => now()->subHour(),
        ]);

        $response = $this->actingAs($this->mechanic)->get(
            route('components.show', $this->component->comp_id)
        );

        $response->assertOk()
            ->assertSee('Calendar Hour')
            ->assertSee('Work Hour')
            ->assertSee('Man Hour')
            ->assertSee('Crew Aktif')
            ->assertSee('Budi');
    }
}
