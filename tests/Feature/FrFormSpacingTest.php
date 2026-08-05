<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Ruang (spacing) pada form FR pernah hilang karena perbaikannya hanya
 * diterapkan di PDF, tidak di form web. Test ini menjaga keduanya sejalan.
 */
class FrFormSpacingTest extends TestCase
{
    use RefreshDatabase;

    private function openForm()
    {
        Role::findOrCreate('Mechanic', 'web');
        $user = User::create([
            'name' => 'Mekanik Spasi',
            'nik' => 'SPACE-' . random_int(1000, 9999),
            'password' => 'password',
        ]);
        $user->assignRole('Mechanic');

        $component = Component::create([
            'serial_number' => 'SPACE-' . random_int(1000, 9999),
            'egi' => 'D155-6',
            'model_type' => 'D155-6',
            'major_category' => 'Control Valve',
            'unit_code' => '2134',
            'current_stage' => 2,
            'status' => 'On Progress',
        ]);

        return $this->actingAs($user)->get(route('components.fr.create', $component->comp_id));
    }

    public function test_form_memakai_sel_fr_val_agar_isian_tidak_menempel_garis(): void
    {
        $response = $this->openForm();

        $response->assertOk();
        // Sel isian blok identitas harus memakai fr-val (rata tengah tegak + ruang kiri)
        $response->assertSee('class="fr-edit fr-val"', false);
        $response->assertSee('.fr-val { vertical-align: middle', false);
        $response->assertSee('.fr-sheet .fr-val input { padding-left: 2px; }', false);
    }

    public function test_ada_celah_antara_header_dan_blok_sent_to(): void
    {
        $response = $this->openForm();

        $response->assertOk();
        // Form asli punya jarak 8pt antara kop dan baris Sent To (garis y=69→77)
        $response->assertSee('<div style="height:10px;"></div>', false);
        // Tabel identitas tidak boleh menempel ke header lewat border-top:none
        $response->assertDontSee('{{-- ============ IDENTITAS + APPROVAL ============ --}}
            <table style="border-top:none;">', false);
    }
}
