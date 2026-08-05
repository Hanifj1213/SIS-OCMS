<?php

namespace Tests\Feature;

use App\Jobs\DuplicateChecksheetGsheets;
use App\Models\Component;
use App\Models\FabricationRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Panel FR/MOL hanya boleh tampil untuk Stage 2 ke atas dan harus mengikuti
 * TAHAP YANG SEDANG DILIHAT. Sebelumnya panel memakai current_stage, sehingga
 * saat mekanik me-review Stage 1 panel Stage 2 tetap muncul.
 */
class FrPanelStageVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private function mechanic(): User
    {
        Role::findOrCreate('Mechanic', 'web');
        $user = User::create([
            'name' => 'Mekanik Panel',
            'nik' => 'PANEL-' . random_int(1000, 9999),
            'password' => 'password',
        ]);
        $user->assignRole('Mechanic');

        return $user;
    }

    private function makeComponent(int $stage): Component
    {
        return Component::create([
            'serial_number' => 'PANEL-' . random_int(1000, 9999),
            'egi' => 'D155-6',
            'model_type' => 'D155-6',
            'major_category' => 'Engine',
            'current_stage' => $stage,
            'status' => 'On Progress',
        ]);
    }

    public function test_panel_fr_tidak_muncul_di_stage_1(): void
    {
        $response = $this->actingAs($this->mechanic())
            ->get(route('components.show', $this->makeComponent(1)->comp_id));

        $response->assertOk();
        $response->assertDontSee('id="fr-panel"', false);
        $response->assertDontSee('Fabrication Request (PLO/09/F-021)');
    }

    public function test_panel_fr_muncul_di_stage_2(): void
    {
        $response = $this->actingAs($this->mechanic())
            ->get(route('components.show', $this->makeComponent(2)->comp_id));

        $response->assertOk();
        $response->assertSee('id="fr-panel"', false);
    }

    public function test_panel_fr_tersembunyi_saat_mereview_stage_1(): void
    {
        $user = $this->mechanic();
        $component = $this->makeComponent(4);

        // Tanpa review: panel tampil
        $this->actingAs($user)
            ->get(route('components.show', $component->comp_id))
            ->assertSee('id="fr-panel"', false);

        // Me-review Stage 1: panel FR/MOL harus ikut tersembunyi
        $this->actingAs($user)
            ->get(route('components.show', ['component' => $component->comp_id, 'review_stage' => 1]))
            ->assertOk()
            ->assertDontSee('id="fr-panel"', false);
    }

    public function test_tanpa_gate_approval_tombol_scan_langsung_tersedia_di_stage_2(): void
    {
        $user = $this->mechanic();

        // Stage 2 yang BELUM di-approve Management: dulu tombol scan diblokir
        // oleh "Approval Gate". Gate itu sudah dihapus atas permintaan user.
        $component = $this->makeComponent(2);
        $component->update(['is_waiting_approval' => true]);

        $response = $this->actingAs($user)
            ->get(route('components.show', $component->comp_id));

        $response->assertOk();
        $response->assertDontSee('Approval Gate Active');
        $response->assertSee('id="fr-scan-btn"', false);
        $response->assertSee(route('components.fr.create', $component->comp_id), false);
        $response->assertSee('https://llk-parts.ru/#', false);
    }

    public function test_panel_mol_tidak_muncul_di_stage_1(): void
    {
        $user = $this->mechanic();
        $component = $this->makeComponent(1);
        $component->partRequests()->create([
            'part_name' => 'SPOOL LUBRICATING VALVE',
            'qty' => 1,
            'order_code' => 'A',
            'status' => 'Pending',
        ]);

        $this->actingAs($user)
            ->get(route('components.show', $component->comp_id))
            ->assertOk()
            ->assertDontSee('Material Order List');
    }

    public function test_pendaftaran_komponen_tidak_memanggil_gsheet_secara_sinkron(): void
    {
        Queue::fake();

        $user = $this->mechanic();
        Role::findOrCreate('SuperAdmin', 'web');
        $user->assignRole('SuperAdmin');

        $response = $this->actingAs($user)->post(route('components.store'), [
            'egi' => 'PC2000-8',
            'unit_code' => '2E',
            'site_district' => 'ADMO',
            'major_category' => 'Engine',
            'serial_number' => 'SYNC-' . random_int(1000, 9999),
            'status_ovh' => 'SCHEDULE',
            'model_type' => 'PC2000-8',
            'component_model' => 'Engine',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        // Duplikasi GSheet harus lewat queue, bukan blocking request:
        // empat template x ~20 detik melewati batas 30 detik PHP.
        Queue::assertPushed(DuplicateChecksheetGsheets::class);
    }
}
