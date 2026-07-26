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
        config(['checksheet_gsheets.webapp_url' => 'https://script.google.com/macros/s/TEST/exec']);
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
        $this->assertGreaterThanOrEqual(35, count($result['rows']));
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
