<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Services\ChecksheetGsheetService;
use App\Services\FabricationRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Scan keputusan Stage 2 memakai data sel asli dari template SIAP
 * (tests/Fixtures/gsheet/*.json, dihasilkan tools/export_decision_fixtures.py).
 */
class DecisionScanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Integrasi GSheet fail-closed: tanpa secret seluruh panggilan ditolak
        // sebelum HTTP dikirim. Test memakai secret dummy + Http::fake().
        config([
            'checksheet_gsheets.webapp_url' => 'https://script.google.com/macros/s/TEST/exec',
            'checksheet_gsheets.secret' => 'test-secret',
        ]);
    }

    /** @return array<string, mixed> */
    private function fixture(string $name): array
    {
        $path = base_path("tests/Fixtures/gsheet/{$name}.json");
        $this->assertFileExists($path, "Fixture {$name} belum dibuat — jalankan tools/export_decision_fixtures.py");

        return json_decode(file_get_contents($path), true);
    }

    private function fakeWebapp(array $payload): void
    {
        Http::fake([
            '*' => Http::response($payload, 200),
        ]);
    }

    private function makeComponent(array $attributes = []): Component
    {
        return Component::create(array_merge([
            'serial_number' => 'SN-' . uniqid(),
            'egi' => 'PC1250-8',
            'major_category' => 'Control Valve',
            'model_type' => 'TEST',
            'unit_code' => 'UT-01',
            'current_stage' => 2,
            'status' => 'On Progress',
        ], $attributes));
    }

    public function test_inspection_scan_reads_every_matching_tab(): void
    {
        $this->fakeWebapp($this->fixture('inspection_cv_pc1250'));

        $component = $this->makeComponent([
            'gsheet_measurement_url' => 'https://docs.google.com/spreadsheets/d/FAKE_ID/edit',
        ]);

        $result = app(ChecksheetGsheetService::class)->readPartDecisionRows($component);

        $this->assertSame('inspection', $result['profile']);
        $this->assertArrayNotHasKey('error', $result);

        $sections = array_unique(array_column($result['rows'], 'sheet'));
        sort($sections);

        // Workbook PC1250-8 punya 3 valve: INSPEKSI NO1/NO2/NO3.
        // Sebelum perbaikan multi-tab, hanya NO1 yang terbaca.
        $this->assertSame(['INSPEKSI NO1', 'INSPEKSI NO2', 'INSPEKSI NO3'], $sections);
        $this->assertGreaterThan(20, count($result['rows']));
    }

    public function test_disassembly_scan_reads_every_matching_tab(): void
    {
        $this->fakeWebapp($this->fixture('disassembly_subassy_engine_d375'));

        $component = $this->makeComponent([
            'egi' => 'D375-6',
            'major_category' => 'Engine',
            'gsheet_subassy_disassembly_url' => 'https://docs.google.com/spreadsheets/d/FAKE_ID/edit',
        ]);

        $result = app(ChecksheetGsheetService::class)->readPartDecisionRows($component);

        $this->assertSame('disassembly', $result['profile']);

        $sections = array_unique(array_column($result['rows'], 'sheet'));

        // Tab pertama (COMPRESSOR DISASSY) tidak punya tabel keputusan;
        // sebelum perbaikan multi-tab, scan berhenti di situ dan hasilnya 0 baris.
        $this->assertContains('CYL HEAD DISASSY', $sections);
        $this->assertContains('SUPPLY PUMP DISASSY', $sections);
        $this->assertContains('TURBO DISASSY', $sections);
        $this->assertGreaterThanOrEqual(30, count($result['rows']));

        $cylinderHeadRows = array_values(array_filter(
            $result['rows'],
            fn ($row) => $row['sheet'] === 'CYL HEAD DISASSY'
        ));
        $this->assertSame(
            [
                'Inserts of valve',
                'Valves',
                'Valve Springs',
                'Measure thickness',
                'Cyl.Head Crack',
                'Air Pressure Test',
            ],
            array_column($cylinderHeadRows, 'part_name')
        );
    }

    public function test_disassembly_reads_unnumbered_continued_block_as_same_part(): void
    {
        $this->fakeWebapp([
            'ok' => true,
            'matched' => true,
            'sheets' => [[
                'name' => 'SUPPLY PUMP DISASSY',
                'values' => [
                    ['NO', 'PART', '', 'CONDITION TO BE INSPECTED', 'REUSE', 'SALVAGE', 'REPLACE'],
                    [7, 'Camshaft', '', 'Remove mounting bolt', false, false, false],
                    ['', '', '', 'More instructions', '', '', ''],
                    ['NO', 'PART', '', 'CONDITION TO BE INSPECTED', 'REUSE', 'SALVAGE', 'REPLACE'],
                    ['', 'Camshaft', '', 'Pull out camshaft', false, true, false],
                    ['', 'continued', '', '', '', '', ''],
                    [8, 'Bearing outer race', '', 'Set tools bearing puller', false, false, false],
                ],
            ]],
        ]);

        $component = $this->makeComponent([
            'egi' => 'D155',
            'major_category' => 'Engine',
            'gsheet_subassy_disassembly_url' => 'https://docs.google.com/spreadsheets/d/FAKE_ID/edit',
        ]);

        $result = app(ChecksheetGsheetService::class)->readPartDecisionRows($component);
        $camshaftRows = array_values(array_filter(
            $result['rows'],
            fn ($row) => $row['part_name'] === 'Camshaft'
        ));

        $this->assertCount(2, $camshaftRows);
        $this->assertSame([7, 7], array_column($camshaftRows, 'no'));
        $this->assertFalse($camshaftRows[0]['needs_repair']);
        $this->assertTrue($camshaftRows[1]['needs_repair']);
    }

    public function test_cylinder_head_disassembly_ignores_numbered_measurement_subtables(): void
    {
        $this->fakeWebapp([
            'ok' => true,
            'matched' => true,
            'sheets' => [[
                'name' => 'CYL HEAD DISASSY',
                'values' => [
                    ['NO', 'PARTS TO REMOVE OR INSPECT', 'CONDITIONS', 'REUSE', 'SALVAGE', 'REPLACE'],
                    [1, 'Inserts of valve', 'Measure valve sinking', false, true, false],
                    [2, 'Valves', 'Measure valve stem', false, false, false],
                    ['', '', 'STANDARD OF VALVE SINKING', '', '', ''],
                    ['NO', 'ENGINE MODEL', 'MEASURING ITEM', '', '', ''],
                    [2, '6D140 & 12V140 SERIES', 'Valve Seat Insert Bore', '', true, ''],
                    [3, '6D170 SERIES', 'Valve Seat Insert Bore', '', '', true],
                    ['NO', 'PARTS TO REMOVE OR INSPECT', 'CONDITIONS', 'REUSE', 'SALVAGE', 'REPLACE'],
                    [7, 'Air Pressure Test', 'Pressurize head', false, false, true],
                    ['NO', 'ITEM TO BE CHECK', 'CYLINDER HEAD NUMBER', '', '', ''],
                    [1, 'Expansion Plug', '', '', true, ''],
                    [2, 'Nozzle Sleeve', '', '', '', true],
                ],
            ]],
        ]);

        $component = $this->makeComponent([
            'major_category' => 'Engine',
            'gsheet_subassy_disassembly_url' => 'https://docs.google.com/spreadsheets/d/FAKE_ID/edit',
        ]);

        $result = app(ChecksheetGsheetService::class)->readPartDecisionRows($component);

        $this->assertSame(
            ['Inserts of valve', 'Valves', 'Air Pressure Test'],
            array_column($result['rows'], 'part_name')
        );
        $this->assertSame([1, 2, 7], array_column($result['rows'], 'no'));
        $this->assertTrue($result['rows'][0]['needs_repair']);
        $this->assertFalse($result['rows'][0]['needs_replace']);
        $this->assertFalse($result['rows'][2]['needs_repair']);
        $this->assertTrue($result['rows'][2]['needs_replace']);
    }

    public function test_numbered_rows_still_work_on_other_disassembly_tabs(): void
    {
        $this->fakeWebapp([
            'ok' => true,
            'matched' => true,
            'sheets' => [[
                'name' => 'TURBO DISASSY',
                'values' => [
                    ['NO', 'PART', 'CONDITION', 'REUSE', 'SALVAGE', 'REPLACE'],
                    [1, 'Turbine housing', 'Check crack', false, true, false],
                    [2, 'Bearing housing', 'Check wear', false, false, true],
                ],
            ]],
        ]);

        $component = $this->makeComponent([
            'major_category' => 'Engine',
            'gsheet_subassy_disassembly_url' => 'https://docs.google.com/spreadsheets/d/FAKE_ID/edit',
        ]);

        $result = app(ChecksheetGsheetService::class)->readPartDecisionRows($component);

        $this->assertSame(
            ['Turbine housing', 'Bearing housing'],
            array_column($result['rows'], 'part_name')
        );
    }

    public function test_unchecked_sheet_produces_no_candidates(): void
    {
        $this->fakeWebapp($this->fixture('inspection_cv_d375'));

        $component = $this->makeComponent([
            'egi' => 'D375-6',
            'gsheet_measurement_url' => 'https://docs.google.com/spreadsheets/d/FAKE_ID/edit',
        ]);

        $result = app(FabricationRequestService::class)->scanCandidates($component);

        // Template kosong (semua decision FALSE) tidak boleh memunculkan FR/PR.
        $this->assertSame([], $result['candidates']);
        $this->assertSame([], $result['part_request_candidates']);
        $this->assertNull($result['gsheet_error']);
    }

    public function test_same_part_name_in_different_sections_yields_separate_candidates(): void
    {
        $values = [
            ['NO', 'PARTS NAME', '', 'DECISION', '', ''],
            ['', '', '', 'U/A', 'U/R', 'R/N'],
            [1, 'SPOOL VALVE', '', false, true, false],
        ];

        $this->fakeWebapp([
            'ok' => true,
            'matched' => true,
            'sheets' => [
                ['name' => 'INSPEKSI NO1', 'values' => $values],
                ['name' => 'INSPEKSI NO2', 'values' => $values],
            ],
        ]);

        $component = $this->makeComponent([
            'gsheet_measurement_url' => 'https://docs.google.com/spreadsheets/d/FAKE_ID/edit',
        ]);

        $result = app(FabricationRequestService::class)->scanCandidates($component);

        // Dua valve fisik berbeda dengan nama part sama = dua FR, bukan satu.
        $this->assertCount(2, $result['candidates']);
        $this->assertSame(
            ['INSPEKSI NO1', 'INSPEKSI NO2'],
            array_column($result['candidates'], 'section')
        );
    }

    public function test_free_text_in_decision_column_is_not_treated_as_checked(): void
    {
        $this->fakeWebapp([
            'ok' => true,
            'matched' => true,
            'sheets' => [[
                'name' => 'INSPEKSI',
                'values' => [
                    ['NO', 'PARTS NAME', '', 'DECISION', '', ''],
                    ['', '', '', 'U/A', 'U/R', 'R/N'],
                    [1, 'BUSHING', '', false, 'N/A', false],
                    [2, 'O-RING', '', false, 'sudah dicek', false],
                    [3, 'SPOOL', '', false, true, false],
                ],
            ]],
        ]);

        $component = $this->makeComponent([
            'gsheet_measurement_url' => 'https://docs.google.com/spreadsheets/d/FAKE_ID/edit',
        ]);

        $result = app(FabricationRequestService::class)->scanCandidates($component);

        // Hanya SPOOL yang benar-benar dicentang.
        $this->assertCount(1, $result['candidates']);
        $this->assertSame('SPOOL', $result['candidates'][0]['part_name']);
    }

    public function test_inspection_reads_decision_from_last_row_of_part_block(): void
    {
        $this->fakeWebapp([
            'ok' => true,
            'matched' => true,
            'sheets' => [[
                'name' => 'inspeksi',
                'values' => [
                    ['NO', 'PARTS NAME', '', 'DECISION', '', ''],
                    ['', '', '', 'U/A', 'U/R', 'R/N'],
                    [1, 'HOUSING', '', false, false, false],
                    ['', 'RH', 'FRONT', false, false, false],
                    ['', '', 'CENTRE', false, false, false],
                    ['', '', 'REAR', false, false, false],
                    ['', 'LH', 'FRONT', false, false, false],
                    ['', '', 'CENTRE', false, false, false],
                    ['', '', 'REAR', false, true, false],
                ],
            ]],
        ]);

        $component = $this->makeComponent([
            'gsheet_measurement_url' => 'https://docs.google.com/spreadsheets/d/FAKE_ID/edit',
        ]);

        $result = app(ChecksheetGsheetService::class)->readInspectionDecisionRows($component);

        $this->assertCount(1, $result['rows']);
        $this->assertSame(3, $result['rows'][0]['row']);
        $this->assertSame(3, $result['rows'][0]['decision_row']);
        $this->assertTrue($result['rows'][0]['needs_repair']);
        $this->assertFalse($result['rows'][0]['needs_replace']);
    }

    public function test_existing_fr_in_another_section_does_not_block_new_candidate(): void
    {
        $values = [
            ['NO', 'PARTS NAME', '', 'DECISION', '', ''],
            ['', '', '', 'U/A', 'U/R', 'R/N'],
            [1, 'SPOOL VALVE', '', false, true, false],
        ];

        $this->fakeWebapp([
            'ok' => true,
            'matched' => true,
            'sheets' => [
                ['name' => 'INSPEKSI NO1', 'values' => $values],
                ['name' => 'INSPEKSI NO2', 'values' => $values],
            ],
        ]);

        $component = $this->makeComponent([
            'gsheet_measurement_url' => 'https://docs.google.com/spreadsheets/d/FAKE_ID/edit',
        ]);

        $component->fabricationRequests()->create([
            'fr_number' => 'FR/SIS/RC/0001/VII/2026/INT',
            'part_name' => 'SPOOL VALVE',
            'section' => 'INSPEKSI NO1',
            'qty' => 1,
            'work_type' => 'repair',
            'source' => 'gsheet',
            'status' => 'draft',
        ]);

        $result = app(FabricationRequestService::class)->scanCandidates($component);

        $this->assertCount(1, $result['candidates']);
        $this->assertSame('INSPEKSI NO2', $result['candidates'][0]['section']);
        $this->assertCount(1, $result['skipped']);
    }
}
