<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Stage 2 tidak boleh fallback ke master GSheet atau form inspeksi digital.
 * Salinan kosong → banner pending; FR/PR lewat scan spreadsheet saja.
 */
class GsheetCopyFallbackTest extends TestCase
{
    use RefreshDatabase;

    private const MASTER_DISASSEMBLY_ID = '1kIjBP4R4MWPkpFzXIU7Smcwnyy2DoR2Pzj2oggmn3tY';

    private function mechanic(): User
    {
        Role::findOrCreate('Mechanic', 'web');
        $user = User::create([
            'name' => 'Mekanik GSheet',
            'nik' => 'GS-' . random_int(1000, 9999),
            'password' => 'password',
        ]);
        $user->assignRole('Mechanic');

        return $user;
    }

    private function makeEngineStage2(array $overrides = []): Component
    {
        return Component::create(array_merge([
            'serial_number' => 'GS-' . random_int(1000, 9999),
            'egi' => 'PC2000-8',
            'model_type' => 'PC2000-8',
            'major_category' => 'Engine',
            'current_stage' => 2,
            'status' => 'On Progress',
        ], $overrides));
    }

    public function test_stage_2_tanpa_gsheet_url_tidak_fallback_master_dan_tanpa_form_digital(): void
    {
        Queue::fake();

        $component = $this->makeEngineStage2([
            'gsheet_url' => null,
            'gsheet_measurement_url' => null,
        ]);

        $response = $this->actingAs($this->mechanic())
            ->get(route('components.show', $component->comp_id));

        $response->assertOk();
        $response->assertDontSee(self::MASTER_DISASSEMBLY_ID, false);
        $response->assertDontSee('Form Inspeksi Digital');
        $response->assertDontSee('Pilih Keputusan');
    }

    public function test_stage_2_tanpa_measurement_url_tidak_menampilkan_form_digital(): void
    {
        Queue::fake();

        $component = $this->makeEngineStage2([
            'egi' => 'D155-6',
            'model_type' => 'D155-6',
            'gsheet_url' => null,
            'gsheet_measurement_url' => null,
            'gsheet_subassy_measurement_url' => null,
        ]);

        $response = $this->actingAs($this->mechanic())
            ->get(route('components.show', $component->comp_id));

        $response->assertOk();
        $response->assertDontSee('Form Inspeksi Digital');
        $response->assertDontSee('Pilih Keputusan');
    }

    public function test_stage_2_tanpa_url_menampilkan_banner_pending_salinan(): void
    {
        Queue::fake();

        $component = $this->makeEngineStage2([
            'egi' => 'D155-6',
            'model_type' => 'D155-6',
            'gsheet_url' => null,
            'gsheet_measurement_url' => null,
        ]);

        $response = $this->actingAs($this->mechanic())
            ->get(route('components.show', $component->comp_id));

        $response->assertOk();
        $response->assertSee('⏳ Salinan Measurement sedang disiapkan');
        $response->assertSee('php artisan queue:work', false);
    }

    public function test_update_stage_2_tanpa_parts_tidak_error_validasi(): void
    {
        Queue::fake();

        $component = $this->makeEngineStage2([
            'gsheet_url' => null,
            'gsheet_measurement_url' => null,
        ]);

        $component->overhaulLogs()->create([
            'stage_number' => 2,
            'mechanic_id' => $this->mechanic()->id,
            'start_time' => now(),
            'notes' => 'Mulai tahap 2',
        ]);

        $response = $this->actingAs($this->mechanic())
            ->post(route('components.updateStage', $component->comp_id), [
                'remarks' => 'Selesai disassembly via GSheet',
            ]);

        $response->assertSessionHasNoErrors();
        $component->refresh();
        $this->assertSame(2, $component->current_stage);
        $this->assertTrue($component->is_waiting_approval);
    }

    public function test_stage_2_dengan_salinan_gsheet_memakai_id_salinan_bukan_master(): void
    {
        Queue::fake();

        $copyDisId = 'COPY-DIS-' . random_int(100000, 999999);
        $copyMeaId = 'COPY-MEA-' . random_int(100000, 999999);

        $component = $this->makeEngineStage2([
            'gsheet_url' => 'https://docs.google.com/spreadsheets/d/' . $copyDisId . '/edit',
            'gsheet_measurement_url' => 'https://docs.google.com/spreadsheets/d/' . $copyMeaId . '/edit',
        ]);

        $response = $this->actingAs($this->mechanic())
            ->get(route('components.show', $component->comp_id));

        $response->assertOk();
        $response->assertSee($copyDisId, false);
        $response->assertSee($copyMeaId, false);
        $response->assertDontSee(self::MASTER_DISASSEMBLY_ID, false);
    }
}
