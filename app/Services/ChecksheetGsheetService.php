<?php

namespace App\Services;

use App\Models\Component;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Menduplikasi template Google Sheets checksheet (per kategori + EGI) menjadi
 * spreadsheet milik satu komponen, via Apps Script Web App.
 *
 * Engine: disassembly, measurement, subassy_*.
 * Powertrain (Control Valve dkk.): disassembly + measurement/inspeksi tahap 2.
 */
class ChecksheetGsheetService
{
    private const KINDS = [
        'disassembly' => [
            'config' => 'disassembly_templates',
            'column' => 'gsheet_url',
            'prefix' => 'DISASSY',
        ],
        'measurement' => [
            'config' => 'measurement_templates',
            'column' => 'gsheet_measurement_url',
            'prefix' => 'MEASUREMENT',
        ],
        'subassy_disassembly' => [
            'config' => 'subassy_disassembly_templates',
            'column' => 'gsheet_subassy_disassembly_url',
            'prefix' => 'SUBASSY DISASSY',
        ],
        'subassy_measurement' => [
            'config' => 'subassy_measurement_templates',
            'column' => 'gsheet_subassy_measurement_url',
            'prefix' => 'SUBASSY MEASUREMENT',
        ],
    ];

    /**
     * ID template jenis tertentu untuk komponen ini, atau null.
     * Lookup: config.{kind}.{major_category}.{EGI}
     */
    public function templateIdFor(Component $component, string $kind = 'disassembly'): ?string
    {
        if (!isset(self::KINDS[$kind])) {
            return null;
        }

        $category = (string) $component->major_category;
        $egi = strtoupper(trim((string) $component->egi));
        $configKey = self::KINDS[$kind]['config'];

        $id = config("checksheet_gsheets.{$configKey}.{$category}.{$egi}");

        // Alias GD825A ↔ GD825A-2
        if (!$id && str_starts_with($egi, 'GD825A')) {
            $alt = $egi === 'GD825A' ? 'GD825A-2' : 'GD825A';
            $id = config("checksheet_gsheets.{$configKey}.{$category}.{$alt}");
        }

        return $id ?: null;
    }

    /**
     * Duplikasi semua jenis template yang tersedia untuk komponen ini.
     */
    public function duplicateForComponent(Component $component): void
    {
        foreach (array_keys(self::KINDS) as $kind) {
            $this->duplicateKind($component, $kind);
        }
    }

    /**
     * Duplikasi satu jenis template dan simpan URL-nya.
     */
    public function duplicateKind(Component $component, string $kind): bool
    {
        $column = self::KINDS[$kind]['column'];

        if ($component->{$column}) {
            return true;
        }

        $templateId = $this->templateIdFor($component, $kind);
        $webappUrl = config('checksheet_gsheets.webapp_url');

        if (!$templateId || !$webappUrl) {
            return false;
        }

        $name = sprintf(
            '%s %s %s - SN %s%s',
            self::KINDS[$kind]['prefix'],
            $component->major_category,
            strtoupper(trim((string) $component->egi)),
            $component->serial_number,
            $component->unit_code ? ' (' . $component->unit_code . ')' : ''
        );

        try {
            $payload = [
                'template_id' => $templateId,
                'name' => $name,
                'secret' => config('checksheet_gsheets.secret', ''),
            ];
            $json = json_encode($payload);

            $response = Http::timeout(20)
                ->withHeaders(['Accept' => 'application/json', 'Content-Type' => 'application/json'])
                ->withBody($json, 'application/json')
                ->withOptions(['allow_redirects' => false])
                ->post($webappUrl);

            if (in_array($response->status(), [301, 302, 303, 307, 308], true)) {
                $location = $response->header('Location');
                if ($location && !str_starts_with($location, 'http')) {
                    $parts = parse_url($webappUrl);
                    $location = ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? '') . $location;
                }
                if ($location) {
                    // GAS: hasil doPost diambil lewat GET ke URL redirect
                    $response = Http::timeout(20)
                        ->withHeaders(['Accept' => 'application/json'])
                        ->withOptions(['allow_redirects' => false])
                        ->get($location);
                }
            }

            $data = $response->json();

            if ($response->successful() && ($data['ok'] ?? false) && !empty($data['url'])) {
                $component->update([$column => $data['url']]);
                return true;
            }

            Log::warning('Duplikasi GSheet gagal', [
                'comp_id' => $component->comp_id,
                'kind' => $kind,
                'category' => $component->major_category,
                'status' => $response->status(),
                'body' => $data,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Duplikasi GSheet error: ' . $e->getMessage(), [
                'comp_id' => $component->comp_id,
                'kind' => $kind,
            ]);
        }

        return false;
    }

    /**
     * Apakah masih ada template yang tersedia tapi belum terduplikasi?
     */
    public function hasPendingDuplication(Component $component): bool
    {
        foreach (self::KINDS as $kind => $meta) {
            if (!$component->{$meta['column']} && $this->templateIdFor($component, $kind)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Profil scan keputusan part: Engine → Disassembly; Powertrain → Inspection.
     */
    public function decisionScanProfile(Component $component): string
    {
        return $component->major_category === 'Engine' ? 'disassembly' : 'inspection';
    }

    /**
     * Baca keputusan part dari GSheet sesuai kategori komponen.
     *
     * @return array{
     *   error?: string,
     *   sheet?: string,
     *   profile: string,
     *   rows: list<array{row:int, no:mixed, part_number:string, part_name:string, needs_repair:bool, needs_replace:bool}>
     * }
     */
    public function readPartDecisionRows(Component $component): array
    {
        if ($this->decisionScanProfile($component) === 'disassembly') {
            return $this->readDisassemblyDecisionRows($component);
        }

        return $this->readInspectionDecisionRows($component);
    }

    /**
     * Engine / disassembly: REUSE | SALVAGE | REPLACE.
     *
     * @return array{error?: string, sheet?: string, profile: string, rows: list<array<string, mixed>>}
     */
    public function readDisassemblyDecisionRows(Component $component): array
    {
        $urls = array_filter([
            $component->gsheet_url,
            $component->gsheet_subassy_disassembly_url,
        ]);

        if ($urls === []) {
            return [
                'error' => 'Komponen belum punya spreadsheet Disassembly.',
                'profile' => 'disassembly',
                'rows' => [],
            ];
        }

        $allRows = [];
        $errors = [];
        $sheetNames = [];

        foreach ($urls as $url) {
            $read = $this->readSpreadsheetValues($url, ['disassy', 'diss', 'disassembly', 'engine']);
            if (!($read['ok'] ?? false)) {
                $errors[] = $read['error'] ?? 'Gagal membaca disassembly';
                continue;
            }

            if (($read['matched'] ?? true) === false) {
                $errors[] = 'Tidak ada tab bernama Disassembly; memakai tab pertama.';
            }

            foreach ($read['sheets'] ?? [] as $tab) {
                $parsed = $this->parseDisassemblyValues($tab['values'] ?? []);
                if ($parsed === []) {
                    continue;
                }

                $sheetNames[] = $tab['name'];
                foreach ($parsed as $row) {
                    $row['sheet'] = $tab['name'];
                    $allRows[] = $row;
                }
            }
        }

        if ($allRows === [] && $errors !== []) {
            return [
                'error' => implode('; ', $errors),
                'profile' => 'disassembly',
                'rows' => [],
            ];
        }

        return [
            'sheet' => $sheetNames === [] ? null : implode(', ', array_unique($sheetNames)),
            'profile' => 'disassembly',
            'rows' => $allRows,
        ];
    }

    /**
     * Powertrain: kolom DECISION U/A | U/R | R/N di sheet Inspection.
     *
     * @return array{error?: string, sheet?: string, profile: string, rows: list<array<string, mixed>>}
     */
    public function readInspectionDecisionRows(Component $component): array
    {
        $url = $component->gsheet_measurement_url ?: $component->gsheet_subassy_measurement_url;
        if (!$url) {
            return [
                'error' => 'Komponen belum punya spreadsheet Measurement/Inspection.',
                'profile' => 'inspection',
                'rows' => [],
            ];
        }

        $read = $this->readSpreadsheetValues($url, ['inspeksi', 'inspection', 'measurement']);
        if (!($read['ok'] ?? false)) {
            return [
                'error' => $read['error'] ?? 'Gagal membaca spreadsheet.',
                'profile' => 'inspection',
                'rows' => [],
            ];
        }

        $allRows = [];
        $sheetNames = [];

        foreach ($read['sheets'] ?? [] as $tab) {
            $parsed = $this->parseInspectionValues($tab['values'] ?? []);
            if ($parsed === []) {
                continue;
            }

            $sheetNames[] = $tab['name'];
            foreach ($parsed as $row) {
                $row['sheet'] = $tab['name'];
                $allRows[] = $row;
            }
        }

        $result = [
            'sheet' => $sheetNames === [] ? ($read['sheet'] ?? null) : implode(', ', array_unique($sheetNames)),
            'profile' => 'inspection',
            'rows' => $allRows,
        ];

        if ($allRows === [] && ($read['matched'] ?? true) === false) {
            $result['error'] = 'Tidak ada tab bernama Inspection/Inspeksi di spreadsheet ini.';
        }

        return $result;
    }

    /** @deprecated Use readInspectionDecisionRows() */
    public function readInspectionRows(Component $component): array
    {
        $result = $this->readInspectionDecisionRows($component);
        unset($result['profile']);

        return $result;
    }

    /**
     * @param  list<string>|null  $sheetKeywords
     * @return array{ok: bool, error?: string, sheet?: string, values?: array<int, array<int, mixed>>, sheets?: list<array{name: string, values: array<int, array<int, mixed>>}>, matched?: bool}
     */
    public function readSpreadsheetValues(string $url, ?array $sheetKeywords = null): array
    {
        $spreadsheetId = $this->extractSpreadsheetId($url);
        if (!$spreadsheetId) {
            return ['ok' => false, 'error' => 'URL spreadsheet tidak valid.'];
        }

        $body = [
            'action' => 'read',
            'spreadsheet_id' => $spreadsheetId,
            'secret' => config('checksheet_gsheets.secret', ''),
        ];

        if ($sheetKeywords !== null) {
            $body['sheet_keywords'] = $sheetKeywords;
        }

        $payload = $this->postWebapp($body);

        if (!$payload || !($payload['ok'] ?? false)) {
            return [
                'ok' => false,
                'error' => $payload['error'] ?? 'Gagal membaca spreadsheet (pastikan webapp sudah di-deploy dengan action read).',
            ];
        }

        // Webapp versi baru mengirim semua tab yang cocok di `sheets`; versi lama
        // hanya `sheet` + `values` (satu tab).
        $sheets = [];
        foreach ($payload['sheets'] ?? [] as $entry) {
            if (!is_array($entry) || !isset($entry['values'])) {
                continue;
            }
            $sheets[] = [
                'name' => (string) ($entry['name'] ?? ''),
                'values' => $entry['values'],
            ];
        }

        if ($sheets === []) {
            $sheets[] = [
                'name' => (string) ($payload['sheet'] ?? ''),
                'values' => $payload['values'] ?? [],
            ];
        }

        return [
            'ok' => true,
            'sheet' => $payload['sheet'] ?? null,
            'values' => $payload['values'] ?? [],
            'sheets' => $sheets,
            'matched' => (bool) ($payload['matched'] ?? true),
        ];
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>|null
     */
    public function postWebapp(array $body): ?array
    {
        $webappUrl = config('checksheet_gsheets.webapp_url');
        if (!$webappUrl) {
            return ['ok' => false, 'error' => 'GSHEET_COPY_WEBAPP_URL belum dikonfigurasi.'];
        }

        try {
            $json = json_encode($body);

            $response = Http::timeout(30)
                ->withHeaders(['Accept' => 'application/json', 'Content-Type' => 'application/json'])
                ->withBody($json, 'application/json')
                ->withOptions(['allow_redirects' => false])
                ->post($webappUrl);

            if (in_array($response->status(), [301, 302, 303, 307, 308], true)) {
                $location = $response->header('Location');
                if ($location && !str_starts_with($location, 'http')) {
                    $parts = parse_url($webappUrl);
                    $location = ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? '') . $location;
                }
                if ($location) {
                    $response = Http::timeout(30)
                        ->withHeaders(['Accept' => 'application/json'])
                        ->withOptions(['allow_redirects' => false])
                        ->get($location);
                }
            }

            return $response->json();
        } catch (\Throwable $e) {
            Log::warning('GSheet webapp error: ' . $e->getMessage());

            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    public function extractSpreadsheetId(string $url): ?string
    {
        if (preg_match('#/spreadsheets/d/([a-zA-Z0-9-_]+)#', $url, $matches)) {
            return $matches[1];
        }

        if (preg_match('#[?&]id=([a-zA-Z0-9-_]+)#', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * @param  array<int, array<int, mixed>>  $values
     * @return list<array{row:int, no:mixed, part_number:string, part_name:string, needs_repair:bool}>
     */
    private function parseInspectionValues(array $values): array
    {
        if ($values === []) {
            return [];
        }

        $headerRow = null;
        $noCol = null;
        $nameCol = null;
        $partNumberCol = null;
        $urCol = null;
        $rnCol = null;

        $scanLimit = min(count($values), 45);
        for ($r = 0; $r < $scanLimit; $r++) {
            $row = $values[$r];
            foreach ($row as $c => $cell) {
                $text = strtoupper(trim((string) $cell));
                if ($text === '') {
                    continue;
                }

                if (preg_match('/^NO\.?$/', $text) || $text === 'NO') {
                    $noCol = $c;
                }
                if (preg_match('/PARTS?\s*NAME/', $text)) {
                    $nameCol = $c;
                    $headerRow = $r;
                }
                if (preg_match('/PART\s*NUMBER|P\/N|PN/', $text)) {
                    $partNumberCol = $c;
                }
            }
        }

        if ($headerRow === null || $nameCol === null) {
            return [];
        }

        if ($noCol === null) {
            $noCol = max(0, $nameCol - 1);
        }

        // Cari sub-header U/R dan R/N (biasanya baris setelah DECISION).
        // Prioritaskan sel yang isinya PERSIS satu label; pencocokan longgar
        // (str_contains) hanya dipakai kalau tidak ada yang persis, supaya sel
        // gabungan "U/A | U/R | R/N" tidak membuat urCol == rnCol.
        $urLoose = null;
        $rnLoose = null;
        for ($r = $headerRow; $r < min($headerRow + 3, count($values)); $r++) {
            foreach ($values[$r] as $c => $cell) {
                $text = strtoupper(trim((string) $cell));
                if ($text === '') {
                    continue;
                }

                if ($text === 'U/R' || $text === 'UR') {
                    $urCol ??= $c;
                } elseif (str_contains($text, 'U/R')) {
                    $urLoose ??= $c;
                }

                if ($text === 'R/N' || $text === 'RN') {
                    $rnCol ??= $c;
                } elseif (str_contains($text, 'R/N')) {
                    $rnLoose ??= $c;
                }
            }
        }

        $urCol ??= $urLoose;
        $rnCol ??= $rnLoose;

        // Satu sel tidak bisa mewakili dua keputusan sekaligus.
        if ($urCol !== null && $urCol === $rnCol) {
            $rnCol = null;
        }

        // Fallback posisional: sel DECISION di-merge di atas U/A | U/R | R/N,
        // jadi U/A = kolom DECISION, U/R = +1, R/N = +2 (lihat
        // tools/siap_decision_standard.md §3). Hanya dipakai kalau sub-header
        // teksnya benar-benar tidak ketemu.
        if ($urCol === null || $rnCol === null) {
            $decisionCol = null;
            foreach ($values[$headerRow] as $c => $cell) {
                if (str_contains(strtoupper(trim((string) $cell)), 'DECISION')) {
                    $decisionCol = $c;
                    break;
                }
            }

            if ($decisionCol !== null) {
                $urCol ??= $decisionCol + 1;
                $rnCol ??= $decisionCol + 2;
            }
        }

        $rows = [];
        $dataStart = $headerRow + 1;

        for ($r = $dataStart; $r < count($values); $r++) {
            $row = $values[$r];
            $noRaw = $row[$noCol] ?? null;
            $nameRaw = $row[$nameCol] ?? null;

            if ($noRaw === null || trim((string) $noRaw) === '') {
                continue;
            }

            if (!is_numeric($noRaw) && !preg_match('/^\d+(\.\d+)?$/', trim((string) $noRaw))) {
                continue;
            }

            $partName = trim((string) $nameRaw);
            if ($partName === '') {
                continue;
            }

            $partNumber = '';
            if ($partNumberCol !== null && isset($row[$partNumberCol])) {
                $partNumber = trim((string) $row[$partNumberCol]);
            }

            $needsRepair = false;
            if ($urCol !== null && isset($row[$urCol])) {
                $needsRepair = $this->isDecisionChecked($row[$urCol]);
            }

            $needsReplace = false;
            if ($rnCol !== null && isset($row[$rnCol])) {
                $needsReplace = $this->isDecisionChecked($row[$rnCol]);
            }

            $rows[] = [
                'row' => $r + 1,
                'no' => $noRaw,
                'part_number' => $partNumber,
                'part_name' => $partName,
                'needs_repair' => $needsRepair,
                'needs_replace' => $needsReplace,
            ];
        }

        return $rows;
    }

    /**
     * Parser Disassembly — dukung beberapa blok header (section) dalam satu sheet.
     *
     * @param  array<int, array<int, mixed>>  $values
     * @return list<array{row:int, no:mixed, part_number:string, part_name:string, needs_repair:bool, needs_replace:bool}>
     */
    private function parseDisassemblyValues(array $values): array
    {
        if ($values === []) {
            return [];
        }

        $rows = [];
        $columns = null;

        for ($r = 0; $r < count($values); $r++) {
            $detected = $this->detectDisassemblyHeaderColumns($values[$r]);
            if ($detected !== null) {
                $columns = $detected;
                continue;
            }

            if ($columns === null) {
                continue;
            }

            $row = $values[$r];
            $noRaw = $row[$columns['noCol']] ?? null;
            $nameRaw = $row[$columns['nameCol']] ?? null;

            if ($noRaw === null || trim((string) $noRaw) === '') {
                continue;
            }

            if (!is_numeric($noRaw) && !preg_match('/^\d+(\.\d+)?$/', trim((string) $noRaw))) {
                continue;
            }

            $partName = trim((string) $nameRaw);
            if ($partName === '') {
                continue;
            }

            $partNumber = '';
            if ($columns['partNumberCol'] !== null && isset($row[$columns['partNumberCol']])) {
                $partNumber = trim((string) $row[$columns['partNumberCol']]);
            }

            $needsRepair = $columns['salvageCol'] !== null
                && isset($row[$columns['salvageCol']])
                && $this->isDecisionChecked($row[$columns['salvageCol']]);

            $needsReplace = $columns['replaceCol'] !== null
                && isset($row[$columns['replaceCol']])
                && $this->isDecisionChecked($row[$columns['replaceCol']]);

            $rows[] = [
                'row' => $r + 1,
                'no' => $noRaw,
                'part_number' => $partNumber,
                'part_name' => $partName,
                'needs_repair' => $needsRepair,
                'needs_replace' => $needsReplace,
            ];
        }

        return $rows;
    }

    /**
     * @param  array<int, mixed>  $row
     * @return array{noCol:int, nameCol:int, partNumberCol:?int, reuseCol:?int, salvageCol:?int, replaceCol:?int}|null
     */
    private function detectDisassemblyHeaderColumns(array $row): ?array
    {
        $noCol = null;
        $nameCol = null;
        $partNumberCol = null;
        $reuseCol = null;
        $salvageCol = null;
        $replaceCol = null;

        foreach ($row as $c => $cell) {
            $text = strtoupper(trim((string) $cell));
            if ($text === '') {
                continue;
            }

            if (preg_match('/^NO\.?$/', $text)) {
                $noCol = $c;
            }
            // Judul kolom nama part bervariasi antar template: "PARTS",
            // "PART", "PARTS NAME", "PARTS TO REMOVE OR INSPECT".
            if (preg_match('/PARTS?\s*TO\s*REMOVE|PARTS?\s*NAME|^PARTS?$/', $text)) {
                $nameCol = $c;
            }
            if (preg_match('/PART\s*NUMBER|P\/N/', $text)) {
                $partNumberCol = $c;
            }
            if ($text === 'REUSE' || $text === 'REUSED') {
                $reuseCol = $c;
            }
            if (in_array($text, ['SALVAGE', 'SALVG', 'SALV'], true) || str_starts_with($text, 'SALV')) {
                $salvageCol = $c;
            }
            if ($text === 'REPLACE' || $text === 'REPLACE NEW') {
                $replaceCol = $c;
            }
        }

        if ($nameCol === null) {
            return null;
        }

        if ($noCol === null) {
            $noCol = max(0, $nameCol - 1);
        }

        // Template standar Engine: REUSE + SALVAGE + REPLACE
        if ($reuseCol !== null && $salvageCol !== null && $replaceCol !== null) {
            return compact('noCol', 'nameCol', 'partNumberCol', 'reuseCol', 'salvageCol', 'replaceCol');
        }

        // Variasi D375 Disassembly: REPAIR | SALVG | REPAIR — pakai SALVG saja
        if ($salvageCol !== null) {
            return compact('noCol', 'nameCol', 'partNumberCol', 'reuseCol', 'salvageCol', 'replaceCol');
        }

        return null;
    }

    /**
     * Daftar-putih: hanya nilai centang yang eksplisit dihitung.
     *
     * Template SIAP sudah dinormalisasi ke boolean (checkbox GSheet), jadi teks
     * bebas seperti "N/A", "OK", atau catatan yang meleber ke kolom keputusan
     * TIDAK boleh ikut memicu FR/PR.
     */
    private function isDecisionChecked(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if ($value === null || $value === '') {
            return false;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value === 1.0;
        }

        $text = strtolower(trim((string) $value));

        return in_array($text, [
            'x', '✓', '√', 'v', 'yes', 'y', 'true', '1', '●', '☑', '✔', 'checked', 'ya',
        ], true);
    }
}
