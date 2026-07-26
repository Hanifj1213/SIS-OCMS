<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\ComponentSpreadsheetAnswer;
use App\Models\SpreadsheetLayout;
use App\Models\User;
use App\Services\SpreadsheetHtmlRenderer;
use App\Services\SpreadsheetLayoutImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Checksheet spreadsheet lokal: impor dari .xlsx asli, render sebagai HTML,
 * dan simpan centang keputusan ke database — tanpa Google Sheets.
 */
class LocalChecksheetTest extends TestCase
{
    use RefreshDatabase;

    private const TEMPLATE = 'CHECKSHEET FOR PROCESS DEVELOPMEN ROBBY/POWERTRAIN/_SIAP_UPLOAD_GSHEET/'
        . 'Control Valve/D375-6/INSPECTION Control Valve D375-6.xlsx';

    private function templatePath(): string
    {
        $path = base_path(self::TEMPLATE);

        if (!is_file($path)) {
            $this->markTestSkipped('Template SIAP tidak tersedia di mesin ini.');
        }

        return $path;
    }

    private function importTemplate(): SpreadsheetLayout
    {
        return app(SpreadsheetLayoutImporter::class)->import($this->templatePath(), [
            'major_category' => 'Control Valve',
            'egi_model' => 'D375-6',
            'kind' => 'inspection',
        ]);
    }

    private function mechanic(): User
    {
        Role::findOrCreate('Mechanic', 'web');

        $user = User::create([
            'name' => 'Mekanik Uji',
            'nik' => 'LC-' . uniqid(),
            'password' => 'password',
        ]);
        $user->assignRole('Mechanic');

        return $user;
    }

    private function makeComponent(): Component
    {
        return Component::create([
            'serial_number' => 'LC-' . uniqid(),
            'model_type' => 'D375-6',
            'major_category' => 'Control Valve',
            'egi' => 'D375-6',
            'current_stage' => 2,
            'status' => 'On Progress',
        ]);
    }

    public function test_import_detects_decision_columns_from_the_real_template(): void
    {
        $layout = $this->importTemplate();

        $this->assertSame(1, $layout->sheet_count);
        $this->assertGreaterThan(0, $layout->part_row_count);

        $sheet = $layout->sheets()[0];
        $map = $layout->decision_map[$sheet['name']];

        $this->assertSame('inspection', $map['profile']);

        // Sub-header U/A | U/R | R/N berada satu baris di bawah "PARTS NAME";
        // ketiganya harus tetap terdeteksi.
        $this->assertSame(['U/A', 'U/R', 'R/N'], array_keys($map['parts'][0]['cells']));
        $this->assertNotSame('', $map['parts'][0]['name']);
    }

    public function test_layout_survives_compression_round_trip(): void
    {
        $layout = $this->importTemplate();
        $reloaded = SpreadsheetLayout::find($layout->layout_id);

        $this->assertSame(
            $layout->sheets()[0]['name'],
            $reloaded->sheets()[0]['name']
        );
        $this->assertNotEmpty($reloaded->styles());
        $this->assertNotEmpty($reloaded->sheets()[0]['cells']);
    }

    public function test_renderer_produces_a_checkbox_for_every_decision_cell(): void
    {
        $layout = $this->importTemplate();
        $sheet = $layout->sheets()[0];
        $map = $layout->decision_map[$sheet['name']];

        $html = (string) app(SpreadsheetHtmlRenderer::class)
            ->render($sheet, $layout->styles(), [], $map, true);

        $expected = array_sum(array_map(static fn ($p) => count($p['cells']), $map['parts']));

        $this->assertSame($expected, substr_count($html, 'class="xl-decision"'));
        $this->assertStringContainsString('<table class="xl-sheet">', $html);
        $this->assertStringContainsString('rowspan=', $html);   // merge ikut terbawa
    }

    public function test_component_page_renders_and_marks_saved_decisions(): void
    {
        $layout = $this->importTemplate();
        $component = $this->makeComponent();
        $sheet = $layout->sheets()[0];
        $ref = $layout->decision_map[$sheet['name']]['parts'][0]['cells']['U/R'];

        ComponentSpreadsheetAnswer::create([
            'comp_id' => $component->comp_id,
            'layout_id' => $layout->layout_id,
            'sheet' => $sheet['name'],
            'cell_ref' => $ref,
            'value' => '1',
        ]);

        $response = $this->actingAs($this->mechanic())
            ->get(route('components.local-checksheet', [$component->comp_id, 'inspection']));

        $response->assertOk();
        $response->assertSee('data-ref="' . $ref . '"', false);
        $response->assertSee('checked', false);
    }

    public function test_mechanic_can_save_a_decision_cell(): void
    {
        $layout = $this->importTemplate();
        $component = $this->makeComponent();
        $sheet = $layout->sheets()[0];
        $ref = $layout->decision_map[$sheet['name']]['parts'][0]['cells']['U/R'];

        $this->actingAs($this->mechanic())
            ->postJson(route('components.local-checksheet.cell', [$component->comp_id, 'inspection']), [
                'sheet' => $sheet['name'],
                'cell_ref' => $ref,
                'value' => '1',
            ])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertDatabaseHas('component_spreadsheet_answers', [
            'comp_id' => $component->comp_id,
            'sheet' => $sheet['name'],
            'cell_ref' => $ref,
            'value' => '1',
        ]);
    }

    public function test_cells_outside_the_decision_columns_are_rejected(): void
    {
        $layout = $this->importTemplate();
        $component = $this->makeComponent();
        $sheet = $layout->sheets()[0];

        // A1 adalah sel judul, bukan kolom keputusan.
        $this->actingAs($this->mechanic())
            ->postJson(route('components.local-checksheet.cell', [$component->comp_id, 'inspection']), [
                'sheet' => $sheet['name'],
                'cell_ref' => 'A1',
                'value' => '1',
            ])
            ->assertStatus(422);

        $this->assertDatabaseCount('component_spreadsheet_answers', 0);
    }

    public function test_layout_lookup_falls_back_to_generic_egi(): void
    {
        $this->importTemplate();

        SpreadsheetLayout::create([
            'major_category' => 'Control Valve',
            'egi_model' => null,
            'kind' => 'inspection',
            'source_file' => 'generic.xlsx',
            'layout' => ['sheets' => [], 'styles' => []],
            'decision_map' => [],
        ]);

        $exact = Component::make(['major_category' => 'Control Valve', 'egi' => 'D375-6']);
        $other = Component::make(['major_category' => 'Control Valve', 'egi' => 'BELUM-ADA']);

        $this->assertSame('D375-6', SpreadsheetLayout::forComponent($exact, 'inspection')->egi_model);
        $this->assertNull(SpreadsheetLayout::forComponent($other, 'inspection')->egi_model);
    }
}
