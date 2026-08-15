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
    /**
     * Cast nilai sel spreadsheet ke string dengan aman.
     *
     * Beberapa response Apps Script bisa mengirim sel sebagai array (misal
     * rich-text runs atau cell gabungan yang tidak terduga) — `(string)`
     * langsung terhadap array memicu fatal "Array to string conversion" dan
     * menggagalkan seluruh scan. Di sini array diratakan jadi teks, nilai
     * lain (null, bool, angka) diperlakukan seperti cast string biasa.
     */
    private function cellToString(mixed $value): string
    {
        if (is_array($value)) {
            return implode(' ', array_map(fn ($v) => $this->cellToString($v), $value));
        }

        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '';
        }

        return (string) $value;
    }

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
        'sdr' => [
            'config' => 'sdr_templates',
            'column' => 'gsheet_sdr_url',
            'prefix' => 'SDR',
        ],
        'assembly' => [
            'config' => 'assembly_templates',
            'column' => 'gsheet_assembly_url',
            'prefix' => 'ASSEMBLY',
        ],
        'testbench' => [
            'config' => 'testbench_templates',
            'column' => 'gsheet_testbench_url',
            'prefix' => 'TESTBENCH',
        ],
    ];

    /** Daftar kind template yang dikenal (untuk UI Developer). */
    public static function kinds(): array
    {
        return array_keys(self::KINDS);
    }

    /**
     * ID template jenis tertentu untuk komponen ini, atau null.
     * Lookup: tabel gsheet_templates DULU (dikelola Developer dari UI),
     * lalu fallback config.{kind}.{major_category}.{EGI} / default.
     */
    public function templateIdFor(Component $component, string $kind = 'disassembly'): ?string
    {
        if (!isset(self::KINDS[$kind])) {
            return null;
        }

        $category = (string) $component->major_category;
        $egi = strtoupper(trim((string) $component->egi));
        $configKey = self::KINDS[$kind]['config'];

        $id = $this->dbTemplateId($kind, $category, $egi)
            ?? config("checksheet_gsheets.{$configKey}.{$category}.{$egi}");

        // Alias GD825A ↔ GD825A-2
        if (!$id && str_starts_with($egi, 'GD825A')) {
            $alt = $egi === 'GD825A' ? 'GD825A-2' : 'GD825A';
            $id = $this->dbTemplateId($kind, $category, $alt)
                ?? config("checksheet_gsheets.{$configKey}.{$category}.{$alt}");
        }

        if (!$id) {
            $id = $this->dbTemplateId($kind, null, null)
                ?? config("checksheet_gsheets.{$configKey}.default");
        }

        return $id ?: null;
    }

    /**
     * Cari ID template di tabel gsheet_templates. Aman dipanggil sebelum
     * tabelnya ada (mis. migrasi belum jalan) — cukup fallback ke config.
     */
    private function dbTemplateId(string $kind, ?string $category, ?string $egi): ?string
    {
        try {
            $query = \App\Models\GsheetTemplate::query()->where('kind', $kind);

            if ($category === null && $egi === null) {
                $query->whereNull('major_category')->whereNull('egi');
            } else {
                $query->where('major_category', $category)
                    ->whereRaw('UPPER(egi) = ?', [strtoupper((string) $egi)]);
            }

            $id = $query->value('spreadsheet_id');

            return ($id !== null && $id !== '') ? $id : null;
        } catch (\Throwable) {
            return null;
        }
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
            // Lewat postWebapp() agar secret, allow-list action, timeout, dan
            // logging aman berlaku seragam untuk seluruh panggilan Apps Script.
            $data = $this->postWebapp([
                'action' => 'copy',
                'template_id' => $templateId,
                'name' => $name,
            ]);

            if (is_array($data) && ($data['ok'] ?? false) && !empty($data['url'])) {
                $component->update([$column => $data['url']]);

                return true;
            }

            // Catat metadata saja — tanpa secret dan tanpa isi response.
            Log::warning('Duplikasi GSheet gagal', [
                'comp_id' => $component->comp_id,
                'kind' => $kind,
                'category' => $component->major_category,
                'error' => is_array($data) ? ($data['error'] ?? 'unknown') : 'response tidak valid',
                'correlation_id' => is_array($data) ? ($data['correlation_id'] ?? null) : null,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Duplikasi GSheet error', [
                'comp_id' => $component->comp_id,
                'kind' => $kind,
                'error' => $e->getMessage(),
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

        // Untuk Powertrain (seperti Control Valve D155-6), baca Inspeksi DULU.
        // Jika ada gsheet_url (Disassembly), gabungkan pindaian Disassembly
        // agar keputusan SALVAGE / REPLACE di sheet Disassembly tidak terlewat!
        $inspectionResult = $this->readInspectionDecisionRows($component);

        if ($component->gsheet_url) {
            $disassemblyResult = $this->readDisassemblyDecisionRows($component);
            if (!empty($disassemblyResult['rows'])) {
                $inspectionRows = $inspectionResult['rows'] ?? [];
                $disassemblyRows = $disassemblyResult['rows'];

                $mergedRows = array_merge($inspectionRows, $disassemblyRows);
                $sheets = array_filter([
                    $inspectionResult['sheet'] ?? null,
                    $disassemblyResult['sheet'] ?? null,
                ]);

                return [
                    'sheet' => implode(', ', array_unique($sheets)),
                    'profile' => 'inspection+disassembly',
                    'rows' => $mergedRows,
                ];
            }
        }

        return $inspectionResult;
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
                $parsed = $this->parseDisassemblyValues(
                    $tab['values'] ?? [],
                    (string) ($tab['name'] ?? '')
                );
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
                'error' => implode('; ', array_unique($errors)),
                'profile' => 'disassembly',
                'rows' => [],
            ];
        }

        $result = [
            'sheet' => $sheetNames === [] ? null : implode(', ', array_unique($sheetNames)),
            'profile' => 'disassembly',
            'rows' => $allRows,
        ];
        if ($errors !== []) {
            $result['warning'] = implode('; ', array_unique($errors));
        }

        return $result;
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

        // Hanya spreadsheet milik OCMS (salinan komponen atau template
        // terdaftar) yang boleh dibaca atas nama aplikasi.
        if (!$this->isManagedSpreadsheetId($spreadsheetId)) {
            return ['ok' => false, 'error' => 'Spreadsheet tidak terdaftar pada OCMS.'];
        }

        $body = [
            'action' => 'read',
            'spreadsheet_id' => $spreadsheetId,
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
     * Aksi runtime yang boleh dipanggil aplikasi. Aksi administratif
     * (restore/format/list revision) sengaja TIDAK ada di sini — aksi itu
     * hanya boleh dijalankan lewat tools CLI pada deployment terpisah yang
     * mengaktifkan OCMS_ADMIN_ACTIONS di Apps Script.
     *
     * @var list<string>
     */
    private const ALLOWED_ACTIONS = ['copy', 'upload', 'read', 'ping'];

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

        // Fail-closed: tanpa secret, jangan pernah memanggil webapp. Apps
        // Script juga menolak, tapi permintaan tidak perlu dikirim sama sekali.
        $secret = (string) config('checksheet_gsheets.secret', '');
        if ($secret === '') {
            return [
                'ok' => false,
                'error' => 'GSHEET_COPY_SECRET belum dikonfigurasi. Integrasi Google Sheets dinonaktifkan sampai secret diisi.',
            ];
        }

        $action = (string) ($body['action'] ?? 'copy');
        if (!in_array($action, self::ALLOWED_ACTIONS, true)) {
            return ['ok' => false, 'error' => 'Action tidak diizinkan: '.$action];
        }

        $body['secret'] = $secret;

        // Correlation ID untuk menelusuri satu permintaan lintas log tanpa
        // pernah mencatat secret atau isi spreadsheet.
        $correlationId = (string) \Illuminate\Support\Str::uuid();

        require_once base_path('tools/apps_script_http.php');

        // Action read (multi-tab) butuh lebih lama; cold start GAS sering > 30 detik di Windows.
        $httpTimeout = $action === 'read' ? 120 : 45;
        $lastError = null;

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            try {
                $response = postToAppsScript($webappUrl, $body, $httpTimeout);
                $payload = $response->json();

                if (is_array($payload) && ($payload['ok'] ?? false)) {
                    return $payload;
                }

                $lastError = is_array($payload)
                    ? ($payload['error'] ?? 'Gagal membaca spreadsheet (pastikan webapp sudah di-deploy dengan action read).')
                    : 'Respons webapp tidak valid (HTTP '.$response->status().')';
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();

                // Log hanya metadata — tanpa secret, tanpa body, tanpa URL.
                Log::warning('GSheet webapp gagal', [
                    'correlation_id' => $correlationId,
                    'action' => $action,
                    'attempt' => $attempt,
                    'error' => $lastError,
                ]);

                if ($attempt < 3) {
                    usleep(2000000);
                }
            }
        }

        return ['ok' => false, 'error' => $lastError ?? 'Unknown error', 'correlation_id' => $correlationId];
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
     * Apakah ID spreadsheet ini dikelola OCMS?
     *
     * Sumber sah: URL yang sudah tersimpan pada kolom gsheet_* komponen, atau
     * template yang terdaftar (tabel gsheet_templates / config). ID di luar
     * itu tidak boleh dikirim ke Apps Script atas nama aplikasi.
     */
    public function isManagedSpreadsheetId(string $spreadsheetId): bool
    {
        if ($spreadsheetId === '') {
            return false;
        }

        foreach (self::KINDS as $meta) {
            $exists = Component::query()
                ->whereNotNull($meta['column'])
                ->where($meta['column'], 'like', '%'.$spreadsheetId.'%')
                ->exists();

            if ($exists) {
                return true;
            }
        }

        try {
            if (\App\Models\GsheetTemplate::query()->where('spreadsheet_id', $spreadsheetId)->exists()) {
                return true;
            }
        } catch (\Throwable) {
            // Tabel template belum ada — lanjut cek config.
        }

        foreach (self::KINDS as $meta) {
            $templates = (array) config('checksheet_gsheets.'.$meta['config'], []);
            if ($this->configContainsId($templates, $spreadsheetId)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<mixed>  $templates
     */
    private function configContainsId(array $templates, string $spreadsheetId): bool
    {
        foreach ($templates as $value) {
            if (is_array($value)) {
                if ($this->configContainsId($value, $spreadsheetId)) {
                    return true;
                }

                continue;
            }

            if (is_string($value) && $value === $spreadsheetId) {
                return true;
            }
        }

        return false;
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
                $text = strtoupper(trim($this->cellToString($cell)));
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
                $text = strtoupper(trim($this->cellToString($cell)));
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
                if (str_contains(strtoupper(trim($this->cellToString($cell))), 'DECISION')) {
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

        $partStarts = [];
        for ($r = $dataStart; $r < count($values); $r++) {
            $row = $values[$r];
            $noRaw = $row[$noCol] ?? null;
            $nameRaw = $row[$nameCol] ?? null;
            $noText = trim($this->cellToString($noRaw));

            if ($noRaw === null || $noText === '') {
                continue;
            }

            if (! is_numeric($noRaw) && ! preg_match('/^\d+(\.\d+)?$/', $noText)) {
                continue;
            }

            if (trim($this->cellToString($nameRaw)) === '') {
                continue;
            }

            $partStarts[] = $r;
        }

        for ($i = 0; $i < count($partStarts); $i++) {
            $start = $partStarts[$i];
            $end = isset($partStarts[$i + 1]) ? $partStarts[$i + 1] - 1 : count($values) - 1;
            if ($end < $start) {
                $end = $start;
            }

            $row = $values[$start];

            $partName = trim($this->cellToString($row[$nameCol] ?? ''));
            $partNumber = '';
            if ($partNumberCol !== null && isset($row[$partNumberCol])) {
                $partNumber = trim($this->cellToString($row[$partNumberCol]));
            }

            // Merge vertikal: nilai checkbox di top-left (baris part).
            // Layout lama (tanpa merge): checkbox di baris bawah block.
            // Scan seluruh block supaya keduanya terbaca.
            $needsRepair = $this->blockHasDecisionChecked($values, $start, $end, $urCol);
            $needsReplace = $this->blockHasDecisionChecked($values, $start, $end, $rnCol);

            $rows[] = [
                'row' => $start + 1,
                'decision_row' => $start + 1,
                'no' => $row[$noCol],
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
    private function parseDisassemblyValues(array $values, string $sheetName = ''): array
    {
        if ($values === []) {
            return [];
        }

        $isCylinderHeadDisassembly = $this->isCylinderHeadDisassemblySheet($sheetName);
        $rows = [];
        $columns = null;
        $ignoreNumberedRowsUntilNextHeader = false;
        $partStarts = [];
        $lastPartNo = null;
        $lastPartName = null;

        for ($r = 0; $r < count($values); $r++) {
            $detected = $this->detectDisassemblyHeaderColumns($values[$r]);
            if ($detected !== null) {
                if ($columns !== null && $partStarts !== []) {
                    $rows = array_merge(
                        $rows,
                        $this->parseDisassemblyPartBlocks(
                            $values,
                            $columns,
                            $partStarts,
                            $r - 1,
                            $isCylinderHeadDisassembly
                        )
                    );
                }
                $columns = $detected;
                $ignoreNumberedRowsUntilNextHeader = false;
                $partStarts = [];
                continue;
            }

            if ($columns === null) {
                continue;
            }

            // CYL HEAD DISASSY mencampur tabel part dengan tabel measurement
            // bernomor pada kolom NO/PART yang sama. Setelah header sub-tabel
            // measurement, abaikan angka sampai header keputusan berikutnya.
            if ($isCylinderHeadDisassembly
                && $this->isCylinderHeadDecisionBoundary($values[$r], $columns)
            ) {
                if ($partStarts !== []) {
                    $rows = array_merge(
                        $rows,
                        $this->parseDisassemblyPartBlocks($values, $columns, $partStarts, $r - 1, true)
                    );
                    $partStarts = [];
                }
                $ignoreNumberedRowsUntilNextHeader = true;
                continue;
            }

            if ($ignoreNumberedRowsUntilNextHeader) {
                continue;
            }

            $row = $values[$r];
            $noRaw = $row[$columns['noCol']] ?? null;
            $nameRaw = $row[$columns['nameCol']] ?? null;
            $name = trim($this->cellToString($nameRaw));
            $noText = trim($this->cellToString($noRaw));
            $isNumbered = $noRaw !== null
                && $noText !== ''
                && (is_numeric($noRaw) || preg_match('/^\d+(\.\d+)?$/', $noText));
            $nextName = trim($this->cellToString($values[$r + 1][$columns['nameCol']] ?? ''));
            $isContinuation = !$isNumbered
                && $lastPartNo !== null
                && ($this->isContinuedPartName($name) || $this->isContinuedPartName($nextName));

            if (!$isNumbered && !$isContinuation) {
                continue;
            }

            if ($isNumbered) {
                if ($name === '') {
                    continue;
                }
                $lastPartNo = $noRaw;
                $lastPartName = $name;
            }

            // Pola "Camshaft" diikuti "continued": row pertama menjadi anchor.
            if ($this->isContinuedPartName($name)
                && $partStarts !== []
                && $partStarts[array_key_last($partStarts)]['start'] === $r - 1
            ) {
                continue;
            }

            $partStarts[] = [
                'start' => $r,
                'no' => $lastPartNo,
                'name' => $isContinuation ? ($lastPartName ?: $name) : $name,
            ];
        }

        if ($columns !== null && $partStarts !== []) {
            $rows = array_merge(
                $rows,
                $this->parseDisassemblyPartBlocks(
                    $values,
                    $columns,
                    $partStarts,
                    count($values) - 1,
                    $isCylinderHeadDisassembly
                )
            );
        }

        return $rows;
    }

    private function isCylinderHeadDisassemblySheet(string $sheetName): bool
    {
        $normalized = strtoupper(trim(preg_replace('/\s+/', ' ', $sheetName) ?? ''));

        return str_contains($normalized, 'CYL HEAD')
            && str_contains($normalized, 'DISASS');
    }

    /**
     * @param  array<int, mixed>  $row
     * @param  array{noCol:int, nameCol:int}  $columns
     */
    private function isCylinderHeadDecisionBoundary(array $row, array $columns): bool
    {
        $no = strtoupper(trim($this->cellToString($row[$columns['noCol']] ?? '')));

        return preg_match(
            '/^(?:NO\.?|ACTUAL|ENGINE MODEL|STANDARD OF\b|[A-Z]\.?\s*MEASURE\b|MEASURE\b|VALVE SPRINGS?|VALVE GUIDES?)$/',
            $no
        ) === 1
            || preg_match('/^(?:STANDARD OF|[A-Z]\.?\s*MEASURE|MEASURE)\b/', $no) === 1;
    }

    /**
     * @param  array<int, array<int, mixed>>  $values
     * @param  array{noCol:int, nameCol:int, partNumberCol:?int, reuseCol:?int, salvageCol:?int, replaceCol:?int}  $columns
     * @param  list<array{start:int, no:mixed, name:string}>  $partStarts
     * @param  int  $sectionEnd  indeks baris terakhir sebelum header berikutnya
     * @return list<array{row:int, decision_row:int, no:mixed, part_number:string, part_name:string, needs_repair:bool, needs_replace:bool}>
     */
    private function parseDisassemblyPartBlocks(
        array $values,
        array $columns,
        array $partStarts,
        int $sectionEnd,
        bool $clampCylinderHead = false
    ): array
    {
        $rows = [];

        for ($i = 0; $i < count($partStarts); $i++) {
            $start = $partStarts[$i]['start'];
            $end = isset($partStarts[$i + 1])
                ? $partStarts[$i + 1]['start'] - 1
                : $sectionEnd;
            if ($end < $start) {
                $end = $start;
            }

            if ($clampCylinderHead) {
                for ($r = $start + 1; $r <= $end; $r++) {
                    if ($this->isCylinderHeadDecisionBoundary($values[$r] ?? [], $columns)) {
                        $end = max($start, $r - 1);
                        break;
                    }
                }
            }

            $row = $values[$start];

            $partNumber = '';
            if ($columns['partNumberCol'] !== null && isset($row[$columns['partNumberCol']])) {
                $partNumber = trim($this->cellToString($row[$columns['partNumberCol']]));
            }

            $needsRepair = $this->blockHasDecisionChecked($values, $start, $end, $columns['salvageCol']);
            $needsReplace = $this->blockHasDecisionChecked($values, $start, $end, $columns['replaceCol']);

            $rows[] = [
                'row' => $start + 1,
                'decision_row' => $start + 1,
                'no' => $partStarts[$i]['no'],
                'part_number' => $partNumber,
                'part_name' => $partStarts[$i]['name'],
                'needs_repair' => $needsRepair,
                'needs_replace' => $needsReplace,
            ];
        }

        return $rows;
    }

    private function isContinuedPartName(string $name): bool
    {
        return $name !== '' && preg_match('/\bCONTINUED\b/i', $name) === 1;
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
            $text = strtoupper(trim($this->cellToString($cell)));
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
     * Baca keputusan di seluruh block part — mendukung merge vertikal
     * (nilai di top-left) maupun checkbox di baris bawah.
     *
     * @param  array<int, array<int, mixed>>  $values
     */
    private function blockHasDecisionChecked(array $values, int $start, int $end, ?int $col): bool
    {
        if ($col === null) {
            return false;
        }

        for ($r = $start; $r <= $end; $r++) {
            if ($this->isDecisionChecked($values[$r][$col] ?? null)) {
                return true;
            }
        }

        return false;
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
