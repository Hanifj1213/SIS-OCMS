<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\User;
use Database\Seeders\DeliveryChecksheetTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * E2E: jalankan satu komponen SA12V140E-1 dari Stage 1 (Receiving) sampai
 * Stage 7 (RFU/Delivery) lewat endpoint yang sama dengan yang dipakai UI:
 * updateStage (Mechanic) + approveStage (Management).
 */
class FullStageWalkthroughTest extends TestCase
{
    use RefreshDatabase;

    private User $mechanic;
    private User $management;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();

        Role::findOrCreate('Mechanic', 'web');
        Role::findOrCreate('Management', 'web');

        $this->mechanic = User::create([
            'name' => 'Mekanik E2E',
            'nik' => 'E2E-MEC-' . random_int(1000, 9999),
            'password' => 'password',
        ]);
        $this->mechanic->assignRole('Mechanic');

        $this->management = User::create([
            'name' => 'Manajemen E2E',
            'nik' => 'E2E-MGT-' . random_int(1000, 9999),
            'password' => 'password',
        ]);
        $this->management->assignRole('Management');
    }

    public function test_komponen_berjalan_dari_stage_1_sampai_rfu(): void
    {
        $this->seed(DeliveryChecksheetTemplateSeeder::class);

        $component = Component::create([
            'serial_number' => 'E2E-' . random_int(10000, 99999),
            'egi' => 'SA12V140E-1',
            'model_type' => 'SA12V140E-1',
            'major_category' => 'Engine',
            'current_stage' => 1,
            'status' => 'On Progress',
            // Salinan GSheet per-stage (Stage 2, 4, 5)
            'gsheet_url' => 'https://docs.google.com/spreadsheets/d/DIS/edit',
            'gsheet_measurement_url' => 'https://docs.google.com/spreadsheets/d/MEA/edit',
            'gsheet_assembly_url' => 'https://docs.google.com/spreadsheets/d/ASSY/edit',
            'gsheet_testbench_url' => 'https://docs.google.com/spreadsheets/d/BENCH/edit',
        ]);
        $component->overhaulLogs()->create([
            'stage_number' => 1,
            'mechanic_id' => $this->mechanic->id,
            'start_time' => now(),
            'notes' => 'Komponen diterima di PRC (Receiving)',
        ]);

        // === Stage 1 → 2: Receiving selesai, tanpa approval ===
        $this->actingAs($this->mechanic)
            ->post(route('components.updateStage', $component->comp_id))
            ->assertSessionHasNoErrors();
        $component->refresh();
        $this->assertSame(2, $component->current_stage);
        $this->assertFalse($component->is_waiting_approval);

        // === Stage 2 → 3: pakai GSheet (tanpa form inspeksi), butuh approval ===
        $this->actingAs($this->mechanic)
            ->post(route('components.updateStage', $component->comp_id))
            ->assertSessionHasNoErrors();
        $component->refresh();
        $this->assertSame(2, $component->current_stage);
        $this->assertTrue($component->is_waiting_approval);

        $this->actingAs($this->management)
            ->post(route('components.approveStage', $component->comp_id))
            ->assertSessionHasNoErrors();
        $component->refresh();
        $this->assertSame(3, $component->current_stage);

        // === Stage 3 → 4: Machining & Fabrication, butuh approval ===
        $this->actingAs($this->mechanic)
            ->post(route('components.updateStage', $component->comp_id))
            ->assertSessionHasNoErrors();
        $this->actingAs($this->management)
            ->post(route('components.approveStage', $component->comp_id))
            ->assertSessionHasNoErrors();
        $component->refresh();
        $this->assertSame(4, $component->current_stage);

        // === Stage 4 → 5: Assembly via GSheet, butuh approval ===
        $this->actingAs($this->mechanic)
            ->post(route('components.updateStage', $component->comp_id))
            ->assertSessionHasNoErrors();
        $this->actingAs($this->management)
            ->post(route('components.approveStage', $component->comp_id))
            ->assertSessionHasNoErrors();
        $component->refresh();
        $this->assertSame(5, $component->current_stage);

        // === Stage 5 → 6: Test Bench via GSheet (tanpa oil_pressure), approval ===
        $this->actingAs($this->mechanic)
            ->post(route('components.updateStage', $component->comp_id))
            ->assertSessionHasNoErrors();
        $this->actingAs($this->management)
            ->post(route('components.approveStage', $component->comp_id))
            ->assertSessionHasNoErrors();
        $component->refresh();
        $this->assertSame(6, $component->current_stage);

        // === Stage 6 → 7: Painting selesai, tanpa approval, langsung RFU ===
        $this->actingAs($this->mechanic)
            ->post(route('components.updateStage', $component->comp_id))
            ->assertSessionHasNoErrors();
        $component->refresh();
        $this->assertSame(7, $component->current_stage);
        $this->assertSame('Ready for Use', $component->status);
        $this->assertFalse($component->is_waiting_approval);

        // Log stage 7 langsung ditutup
        $finalLog = $component->overhaulLogs()->where('stage_number', 7)->first();
        $this->assertNotNull($finalLog);
        $this->assertNotNull($finalLog->end_time);

        // Halaman stage 7 menampilkan checksheet Delivery dari template
        $this->actingAs($this->mechanic)
            ->get(route('components.show', $component->comp_id))
            ->assertOk()
            ->assertSee('Flywheel housing');

        // Stage 7 final: tidak bisa maju lagi
        $this->actingAs($this->mechanic)
            ->post(route('components.updateStage', $component->comp_id))
            ->assertSessionHasErrors('stage');
    }

    public function test_management_tidak_bisa_memproses_tahap_dan_mekanik_tidak_bisa_approve(): void
    {
        $component = Component::create([
            'serial_number' => 'E2E-RBAC-' . random_int(10000, 99999),
            'egi' => 'SA12V140E-1',
            'model_type' => 'SA12V140E-1',
            'major_category' => 'Engine',
            'current_stage' => 2,
            'status' => 'On Progress',
            'gsheet_url' => 'https://docs.google.com/spreadsheets/d/DIS/edit',
            'gsheet_measurement_url' => 'https://docs.google.com/spreadsheets/d/MEA/edit',
        ]);

        // Management tidak boleh memproses tahap
        $this->actingAs($this->management)
            ->post(route('components.updateStage', $component->comp_id))
            ->assertSessionHasErrors('stage');

        // Mechanic mengajukan approval
        $this->actingAs($this->mechanic)
            ->post(route('components.updateStage', $component->comp_id))
            ->assertSessionHasNoErrors();

        // Mechanic tidak boleh approve
        $this->actingAs($this->mechanic)
            ->post(route('components.approveStage', $component->comp_id))
            ->assertSessionHasErrors('approval');

        $this->assertSame(2, $component->fresh()->current_stage);
    }
}
