<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\SpreadsheetLayout;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LocalChecksheetIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private function mechanic(): User
    {
        Role::findOrCreate('Mechanic', 'web');
        $user = User::create([
            'name' => 'Mekanik Local',
            'nik' => 'LOC-'.random_int(1000, 9999),
            'password' => 'password',
        ]);
        $user->assignRole('Mechanic');

        return $user;
    }

    private function layout(): SpreadsheetLayout
    {
        return SpreadsheetLayout::create([
            'major_category' => 'Control Valve',
            'egi_model' => 'D375-6',
            'kind' => 'inspection',
            'source_file' => 'fixture.xlsx',
            'layout' => [
                'sheets' => [['name' => 'Sheet1', 'index' => 0]],
                'styles' => [],
            ],
            'decision_map' => [
                'Sheet1' => [
                    'parts' => [
                        ['cells' => ['U/R' => 'E10', 'R/N' => 'F10']],
                    ],
                ],
            ],
        ]);
    }

    private function makeComponent(int $stage = 2): Component
    {
        return Component::create([
            'serial_number' => 'LOC-'.random_int(1000, 9999),
            'major_category' => 'Control Valve',
            'model_type' => 'Control Valve',
            'egi' => 'D375-6',
            'current_stage' => $stage,
            'status' => 'On Progress',
        ]);
    }

    public function test_save_cell_ditolak_pada_tahap_salah(): void
    {
        $this->actingAs($this->mechanic())
            ->postJson(route('components.local-checksheet.cell', [$this->makeComponent(3)->comp_id, 'inspection']), [
                'sheet' => 'Sheet1',
                'cell_ref' => 'E10',
                'value' => '1',
            ])
            ->assertForbidden();
    }

    public function test_save_cell_ditolak_saat_menunggu_approval(): void
    {
        $component = $this->makeComponent(2);
        $component->update(['is_waiting_approval' => true]);

        $this->actingAs($this->mechanic())
            ->postJson(route('components.local-checksheet.cell', [$component->comp_id, 'inspection']), [
                'sheet' => 'Sheet1',
                'cell_ref' => 'E10',
                'value' => '1',
            ])
            ->assertForbidden();
    }

    public function test_save_cell_menolak_nilai_di_luar_allow_list(): void
    {
        $this->layout();

        $this->actingAs($this->mechanic())
            ->postJson(route('components.local-checksheet.cell', [$this->makeComponent()->comp_id, 'inspection']), [
                'sheet' => 'Sheet1',
                'cell_ref' => 'E10',
                'value' => 'SALVAGE',
            ])
            ->assertStatus(422);
    }

    public function test_save_cell_valid_diterima(): void
    {
        $this->layout();
        $component = $this->makeComponent();

        $this->actingAs($this->mechanic())
            ->postJson(route('components.local-checksheet.cell', [$component->comp_id, 'inspection']), [
                'sheet' => 'Sheet1',
                'cell_ref' => 'E10',
                'value' => '1',
            ])
            ->assertOk();

        $this->assertDatabaseHas('component_spreadsheet_answers', [
            'comp_id' => $component->comp_id,
            'cell_ref' => 'E10',
            'value' => '1',
        ]);
    }
}
