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

    private const ENGINE_SUBASSY_DIR = 'CHECKSHEET FOR PROCESS DEVELOPMEN ROBBY/ENGINE/_SIAP_UPLOAD_GSHEET/';

    /** @return array{path:string, egi:string} */
    private function engineSubassyTemplate(string $model): array
    {
        $templates = [
            'D155' => 'SUBASSY DISASSEMBLY ENGINE SAA6D140E-5 (D155).xlsx',
            'PC2000-8' => 'SUBASSY DISASSEMBLY ENGINE SAA12V140E-3 (PC2000-8).xlsx',
        ];
        $path = base_path(self::ENGINE_SUBASSY_DIR . $templates[$model]);

        if (!is_file($path)) {
            $this->markTestSkipped("Template SIAP Engine Subassy {$model} tidak tersedia.");
        }

        return ['path' => $path, 'egi' => $model];
    }

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

    public function test_import_detects_unnumbered_continued_part_block(): void
    {
        $template = $this->engineSubassyTemplate('D155');

        $layout = app(SpreadsheetLayoutImporter::class)->import($template['path'], [
            'major_category' => 'Engine',
            'egi_model' => $template['egi'],
            'kind' => 'subassy_disassembly',
        ]);
        $parts = $layout->decision_map['SUPPLY PUMP DISASSY']['parts'];
        $continued = array_values(array_filter(
            $parts,
            fn ($part) => ($part['continued'] ?? false) === true
        ));

        $this->assertCount(2, $continued);
        $this->assertSame(
            [
                ['no' => '5', 'name' => 'Feed pump', 'row' => 125, 'start' => 125, 'end' => 136],
                ['no' => '7', 'name' => 'Camshaft', 'row' => 178, 'start' => 178, 'end' => 184],
            ],
            array_map(
                static fn ($part) => [
                    'no' => $part['no'],
                    'name' => $part['name'],
                    'row' => $part['row'],
                    'start' => $part['box_start'],
                    'end' => $part['box_end'],
                ],
                $continued
            )
        );
    }

    public function test_cylinder_head_decision_map_excludes_measurement_subtables(): void
    {
        $template = $this->engineSubassyTemplate('D155');

        $layout = app(SpreadsheetLayoutImporter::class)->import($template['path'], [
            'major_category' => 'Engine',
            'egi_model' => $template['egi'],
            'kind' => 'subassy_disassembly',
        ]);
        $map = $layout->decision_map['CYL HEAD DISASSY'];

        $this->assertSame(
            [
                ['no' => '1', 'name' => 'Inserts of valve', 'row' => 15, 'end' => 21],
                ['no' => '2', 'name' => 'Valves', 'row' => 72, 'end' => 80],
                ['no' => '3', 'name' => 'Valve Springs', 'row' => 133, 'end' => 144],
                ['no' => '5', 'name' => 'Measure thickness', 'row' => 238, 'end' => 239],
                ['no' => '6', 'name' => 'Cyl.Head Crack', 'row' => 267, 'end' => 273],
                ['no' => '7', 'name' => 'Air Pressure Test', 'row' => 316, 'end' => 319],
            ],
            array_map(
                static fn ($part) => [
                    'no' => $part['no'],
                    'name' => $part['name'],
                    'row' => $part['row'],
                    'end' => $part['box_end'],
                ],
                $map['parts']
            )
        );

        // Header keputusan tetap semua disimpan untuk audit/formatting.
        $this->assertCount(6, $map['headers']);
    }

    public function test_pc2000_cylinder_head_keeps_multiple_parts_before_measurement_table(): void
    {
        $template = $this->engineSubassyTemplate('PC2000-8');

        $layout = app(SpreadsheetLayoutImporter::class)->import($template['path'], [
            'major_category' => 'Engine',
            'egi_model' => $template['egi'],
            'kind' => 'subassy_disassembly',
        ]);
        $map = $layout->decision_map['CYL HEAD DISASSY'];

        $this->assertSame(
            [
                'Inserts of valve',
                'Cyl.Head Crack',
                'Measure thickness',
                'Air Pressure Test',
                'Valve Springs',
                'Valves',
            ],
            array_column($map['parts'], 'name')
        );
        $this->assertSame([15, 65, 76, 139, 159, 231], array_column($map['parts'], 'row'));
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
