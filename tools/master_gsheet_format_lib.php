<?php

declare(strict_types=1);

/** kind layout → kunci config master GSheet */
const MASTER_GSHEET_KIND_CONFIG = [
    'disassembly' => 'disassembly_templates',
    'subassy_disassembly' => 'subassy_disassembly_templates',
    'inspection' => 'measurement_templates',
    'measurement' => 'measurement_templates',
    'subassy_measurement' => 'subassy_measurement_templates',
];

function masterGsheetTemplateIdFor(string $configKey, string $category, string $egi): ?string
{
    $id = config("checksheet_gsheets.{$configKey}.{$category}.{$egi}");

    if (!$id && str_starts_with($egi, 'GD825A')) {
        $alt = $egi === 'GD825A' ? 'GD825A-2' : 'GD825A';
        $id = config("checksheet_gsheets.{$configKey}.{$category}.{$alt}");
    }

    if (!$id && $egi === 'D155') {
        $id = config("checksheet_gsheets.{$configKey}.{$category}.D155-6")
            ?: config("checksheet_gsheets.{$configKey}.{$category}.D155");
    }

    if (!$id && in_array($egi, ['D375', 'D375-6'], true)) {
        $id = config("checksheet_gsheets.{$configKey}.{$category}.D375-6")
            ?: config("checksheet_gsheets.{$configKey}.{$category}.D375");
    }

    if (!$id && $egi === 'D155-6') {
        $id = config("checksheet_gsheets.{$configKey}.{$category}.D155-6");
    }

    return $id ?: null;
}

/**
 * @return array{col: int, row: int}|null
 */
function masterGsheetParseCellRef(string $ref): ?array
{
    if (!preg_match('/^([A-Z]+)(\d+)$/', strtoupper(trim($ref)), $m)) {
        return null;
    }

    $col = 0;
    foreach (str_split($m[1]) as $ch) {
        $col = $col * 26 + (ord($ch) - 64);
    }

    return ['col' => $col, 'row' => (int) $m[2]];
}

function masterGsheetColRowToRef(int $col, int $row): string
{
    $letters = '';
    while ($col > 0) {
        $rem = ($col - 1) % 26;
        $letters = chr(65 + $rem) . $letters;
        $col = (int) (($col - $rem - 1) / 26);
    }

    return $letters . $row;
}

/**
 * @param  \Illuminate\Database\Eloquent\Collection<int, \App\Models\SpreadsheetLayout>  $layouts
 * @return array<string, array{labels: list<string>, sheets: array<string, array{merges: list<array{col:int,startRow:int,endRow:int,ref:string}>, cells: array<string, true>}>}>
 */
function masterGsheetBuildTargets($layouts, ?string $onlyFilter, ?string $idFilter): array
{
    /** @var array<string, array{labels: list<string>, sheets: array<string, array{merges: list<array{col:int,startRow:int,endRow:int,ref:string}>, cells: array<string, true>}>}> */
    $targets = [];

    foreach ($layouts as $layout) {
        if ($layout->part_row_count <= 0 || $layout->decision_map === []) {
            continue;
        }

        $egi = strtoupper(trim((string) $layout->egi_model));
        if ($onlyFilter && !str_contains($egi, $onlyFilter) && !str_contains($layout->major_category, $onlyFilter)) {
            continue;
        }

        $configKey = MASTER_GSHEET_KIND_CONFIG[$layout->kind] ?? null;
        if (!$configKey) {
            continue;
        }

        $templateId = masterGsheetTemplateIdFor($configKey, $layout->major_category, $egi);
        if (!$templateId) {
            fwrite(STDERR, "  SKIP (no config ID): {$layout->major_category} {$egi} {$layout->kind}\n");
            continue;
        }

        if ($idFilter && $templateId !== $idFilter) {
            continue;
        }

        if (!isset($targets[$templateId])) {
            $targets[$templateId] = ['labels' => [], 'sheets' => []];
        }

        $targets[$templateId]['labels'][] = "{$layout->major_category} {$egi} ({$layout->kind})";

        $sheetMeta = [];
        foreach ($layout->sheets() as $sheet) {
            $sheetMeta[$sheet['name']] = (int) ($sheet['max_row'] ?? 0);
        }

        foreach ($layout->decision_map as $sheetName => $map) {
            if (!isset($targets[$templateId]['sheets'][$sheetName])) {
                $targets[$templateId]['sheets'][$sheetName] = ['boxes' => [], 'clear' => []];
            }

            $decisionCols = array_map('intval', array_values($map['headers'][0]['decisions'] ?? []));
            $headerRows = [];
            foreach ($map['headers'] ?? [] as $headerMeta) {
                $hr = (int) ($headerMeta['row'] ?? 0);
                if ($hr > 0) {
                    $headerRows[$hr] = true;
                }
            }

            $parts = $map['parts'] ?? [];
            usort($parts, fn ($a, $b) => ($a['row'] ?? 0) <=> ($b['row'] ?? 0));

            foreach ($parts as $i => $part) {
                $partRow = (int) ($part['row'] ?? 0);
                if ($partRow <= 0) {
                    continue;
                }

                $boxEnd = (int) ($part['box_end'] ?? $part['decision_row'] ?? $partRow);
                if (isset($parts[$i + 1])) {
                    $boxEnd = min($boxEnd, (int) $parts[$i + 1]['row'] - 1);
                }

                $boxStart = (int) ($part['box_start'] ?? $part['decision_row'] ?? $partRow);
                $boxStart = max($partRow, min($boxStart, $boxEnd));
                if ($boxEnd < $boxStart) {
                    $boxEnd = $boxStart;
                }

                if (isset($headerRows[$boxStart])) {
                    continue;
                }

                foreach ($decisionCols as $col) {
                    $key = "{$col}:{$boxStart}:{$boxEnd}";
                    $targets[$templateId]['sheets'][$sheetName]['boxes'][$key] = [
                        'col' => $col,
                        'startRow' => $boxStart,
                        'endRow' => $boxEnd,
                        'ref' => masterGsheetColRowToRef($col, $boxStart),
                    ];
                }
            }
        }
    }

    foreach ($targets as &$target) {
        $target['labels'] = array_values(array_unique($target['labels']));
        sort($target['labels']);
        foreach ($target['sheets'] as &$sheet) {
            $sheet['boxes'] = array_values($sheet['boxes'] ?? []);
            $sheet['clear'] = array_keys($sheet['clear'] ?? []);
        }
        unset($sheet);
    }
    unset($target);

    return $targets;
}

/**
 * Baris-baris berurutan digabung jadi range A1 ("U73:U88") supaya jumlah
 * operasi di Apps Script jauh lebih sedikit.
 *
 * @param  array<int, array<int, true>>  $clearRows  [kolom => [baris => true]]
 * @return list<string>
 */
function masterGsheetCompressClearRanges(array $clearRows): array
{
    $ranges = [];

    foreach ($clearRows as $col => $rowSet) {
        $rows = array_keys($rowSet);
        sort($rows);

        $start = null;
        $prev = null;

        foreach ($rows as $row) {
            if ($start === null) {
                $start = $prev = $row;
                continue;
            }

            if ($row === $prev + 1) {
                $prev = $row;
                continue;
            }

            $ranges[] = masterGsheetClearRangeRef((int) $col, $start, $prev);
            $start = $prev = $row;
        }

        if ($start !== null) {
            $ranges[] = masterGsheetClearRangeRef((int) $col, $start, $prev);
        }
    }

    return $ranges;
}

function masterGsheetClearRangeRef(int $col, int $startRow, int $endRow): string
{
    $from = masterGsheetColRowToRef($col, $startRow);

    return $startRow === $endRow
        ? $from
        : $from . ':' . masterGsheetColRowToRef($col, $endRow);
}

function masterGsheetTransientFailureReason(\Illuminate\Http\Client\Response $response): ?string
{
    if (in_array($response->status(), [301, 302, 303, 307, 308, 404, 429, 500, 502, 503, 504], true)) {
        return 'HTTP ' . $response->status();
    }

    $body = $response->body();
    $needles = [
        'Halaman Tidak Ditemukan',
        'Page Not Found',
        'Moved Temporarily',
        'Service invoked too many times',
        'Exception: Service timed out',
    ];
    foreach ($needles as $needle) {
        if (str_contains($body, $needle)) {
            return $needle;
        }
    }

    $data = $response->json();
    if (!is_array($data) && trim($body) !== '' && !str_starts_with(ltrim($body), '{')) {
        return 'non-JSON response';
    }

    return null;
}

/**
 * @param  array<string, mixed>  $payload
 */
function masterGsheetPostWithRetry(string $url, array $payload, int $timeout = 120, int $attempts = 3): \Illuminate\Http\Client\Response
{
    require_once __DIR__ . '/apps_script_http.php';

    $last = null;

    for ($i = 1; $i <= $attempts; $i++) {
        try {
            $response = postToAppsScript($url, $payload, $timeout);
            $transient = masterGsheetTransientFailureReason($response);
            if ($transient === null) {
                return $response;
            }

            $last = $response;
            if ($i >= $attempts) {
                return $response;
            }

            fwrite(STDERR, "  retry {$i}/{$attempts} ({$transient})...\n");
            sleep($transient === 'HTTP 429' ? 15 : 5);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            if ($i >= $attempts) {
                throw $e;
            }
            fwrite(STDERR, "  retry {$i}/{$attempts} (timeout)...\n");
            sleep(5);
        }
    }

    if ($last !== null) {
        return $last;
    }

    throw new \RuntimeException('masterGsheetPostWithRetry: unreachable');
}

function masterGsheetPrintAuthHint(string $err): bool
{
    if (!str_contains($err, 'SpreadsheetApp.openById')) {
        return false;
    }

    echo "  GAGAL: Apps Script belum di-authorize untuk Spreadsheet.\n";
    echo "         → Apps Script editor → Run authorizeSpreadsheetAccess → Allow\n";
    echo "         → Deploy New version → ulangi perintah format\n";

    return true;
}

function masterGsheetFormatError(string $raw): string
{
    // Apps Script error page: <div style="...font-family:monospace...">MESSAGE</div>
    if (preg_match('/font-family:\s*monospace[^>]*>\s*([^<]+)/i', $raw, $m)) {
        return trim(html_entity_decode($m[1]));
    }

    if (preg_match('/>([^<]{10,200})</', $raw, $m)) {
        return trim(html_entity_decode(strip_tags($m[1])));
    }

    if (str_contains($raw, 'merged range')) {
        return 'You must select all cells in a merged range to merge or unmerge them.';
    }

    return strlen($raw) > 300 ? substr($raw, 0, 300) . '…' : $raw;
}

/**
 * Semua ID master template unik dari config/checksheet_gsheets.php
 *
 * @return array<string, array{labels: list<string>}>
 */
function masterGsheetCollectTemplateIds(?string $onlyFilter = null, ?string $idFilter = null): array
{
    $config = config('checksheet_gsheets');
    $groups = [
        'disassembly' => 'disassembly_templates',
        'inspection' => 'measurement_templates',
        'subassy_disassembly' => 'subassy_disassembly_templates',
        'subassy_measurement' => 'subassy_measurement_templates',
    ];

    /** @var array<string, array{labels: list<string>}> */
    $ids = [];

    foreach ($groups as $label => $configKey) {
        foreach ($config[$configKey] ?? [] as $category => $egiMap) {
            foreach ($egiMap as $egi => $id) {
                if (!$id) {
                    continue;
                }

                $egiUp = strtoupper((string) $egi);
                if ($onlyFilter
                    && !str_contains($egiUp, $onlyFilter)
                    && !str_contains($category, $onlyFilter)
                ) {
                    continue;
                }
                if ($idFilter && $id !== $idFilter) {
                    continue;
                }

                if (!isset($ids[$id])) {
                    $ids[$id] = ['labels' => []];
                }
                $ids[$id]['labels'][] = "{$category}/{$egi} ({$label})";
            }
        }
    }

    foreach ($ids as &$entry) {
        $entry['labels'] = array_values(array_unique($entry['labels']));
        sort($entry['labels']);
    }
    unset($entry);

    ksort($ids);

    return $ids;
}

/** @var list<string> */
const MASTER_GSHEET_XLSX_ROOTS = [
    'CHECKSHEET FOR PROCESS DEVELOPMEN ROBBY/ENGINE/_SIAP_UPLOAD_GSHEET',
    'CHECKSHEET FOR PROCESS DEVELOPMEN ROBBY/POWERTRAIN/_SIAP_UPLOAD_GSHEET',
];

/**
 * Map master spreadsheet ID → file .xlsx SIAP untuk restore.
 *
 * @return array<string, array{labels: list<string>, xlsx: string, xlsx_name: string}>
 */
function masterGsheetBuildXlsxRestoreTargets(?string $onlyFilter = null, ?string $idFilter = null): array
{
    /** @var array<string, array{labels: list<string>, xlsx: string, xlsx_name: string}> */
    $targets = [];

    foreach (MASTER_GSHEET_XLSX_ROOTS as $rootRel) {
        $dir = base_path($rootRel);
        if (!is_dir($dir)) {
            continue;
        }

        $isEngine = str_contains($rootRel, '/ENGINE/');
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($it as $file) {
            if (!$file->isFile() || !preg_match('/\.xlsx$/i', $file->getFilename())) {
                continue;
            }
            if (str_starts_with($file->getFilename(), '~$')) {
                continue;
            }

            $path = $file->getPathname();
            $name = $file->getFilename();
            $kind = masterGsheetKindFromFilename($name);
            if ($kind === null) {
                continue;
            }

            $configKey = MASTER_GSHEET_KIND_CONFIG[$kind] ?? null;
            if (!$configKey) {
                continue;
            }

            $rel = str_replace('\\', '/', substr($path, strlen(base_path()) + 1));

            if ($isEngine) {
                foreach (masterGsheetEgisFromEngineName($name) as $egi) {
                    masterGsheetAddXlsxTarget(
                        $targets,
                        'Engine',
                        $egi,
                        $kind,
                        $configKey,
                        $path,
                        $name,
                        $onlyFilter,
                        $idFilter
                    );
                }
                continue;
            }

            $parts = explode('/', $rel);
            $count = count($parts);
            $category = $parts[$count - 3] ?? 'Control Valve';
            $egi = $parts[$count - 2] ?? null;

            masterGsheetAddXlsxTarget(
                $targets,
                $category,
                $egi ? strtoupper($egi) : null,
                $kind,
                $configKey,
                $path,
                $name,
                $onlyFilter,
                $idFilter
            );
        }
    }

    foreach ($targets as &$target) {
        $target['labels'] = array_values(array_unique($target['labels']));
        sort($target['labels']);
    }
    unset($target);

    ksort($targets);

    return $targets;
}

function masterGsheetKindFromFilename(string $filename): ?string
{
    $upper = strtoupper($filename);

    return match (true) {
        str_contains($upper, 'SUBASSY DISASSEMBLY') => 'subassy_disassembly',
        str_contains($upper, 'SUBASSY MEASUREMENT') => 'subassy_measurement',
        str_contains($upper, 'DISASSEMBLY') => 'disassembly',
        str_contains($upper, 'INSPECTION') => 'inspection',
        str_contains($upper, 'MEASUREMENT') => 'measurement',
        default => null,
    };
}

/** @return list<string|null> */
function masterGsheetEgisFromEngineName(string $filename): array
{
    if (!preg_match('/\(([^)]+)\)/', $filename, $m)) {
        return [null];
    }

    $egis = preg_split('/[\s,]+/', trim($m[1])) ?: [];
    $egis = array_values(array_filter(array_map('strtoupper', $egis)));

    return $egis === [] ? [null] : $egis;
}

/**
 * @param  array<string, array{labels: list<string>, xlsx: string, xlsx_name: string}>  $targets
 */
function masterGsheetAddXlsxTarget(
    array &$targets,
    string $category,
    ?string $egi,
    string $kind,
    string $configKey,
    string $path,
    string $name,
    ?string $onlyFilter,
    ?string $idFilter
): void {
    $egi = $egi ? strtoupper(trim($egi)) : '';
    if ($onlyFilter
        && !str_contains($egi, $onlyFilter)
        && !str_contains($category, $onlyFilter)
        && !str_contains($name, $onlyFilter)
    ) {
        return;
    }

    $templateId = masterGsheetTemplateIdFor($configKey, $category, $egi);
    if (!$templateId) {
        return;
    }
    if ($idFilter && $templateId !== $idFilter) {
        return;
    }

    if (!isset($targets[$templateId])) {
        $targets[$templateId] = [
            'labels' => [],
            'xlsx' => $path,
            'xlsx_name' => $name,
        ];
    }

    $targets[$templateId]['labels'][] = "{$category}/{$egi} ({$kind})";
}
