<?php
/**
 * Pasang checkbox keputusan part di SEMUA master template Google Sheets.
 *
 * Daftar sel diambil dari decision_map layout lokal (hasil impor SIAP —
 * sudah diinspeksi verify_siap_state.py). Satu spreadsheet ID unik per
 * master; salinan komponen otomatis warisi format saat duplicate.
 *
 *   php tools/apply_master_checkboxes.php --dry-run
 *   php tools/apply_master_checkboxes.php --apply
 *   php tools/apply_master_checkboxes.php --apply --only=PC1250-8
 *   php tools/apply_master_checkboxes.php --apply --id=1iqb7_rZwRxy3BHl863jbZ7utF8S1t-OOkrvsk4deApc
 *
 * WAJIB: deploy ulang tools/gsheet_copy_webapp.gs (New version) sebelum --apply.
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
require __DIR__ . '/apps_script_http.php';

use App\Models\SpreadsheetLayout;

/** @var list<string> */
$argv = $_SERVER['argv'] ?? [];

$dryRun = !in_array('--apply', $argv, true);
$onlyFilter = null;
$idFilter = null;

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--only=')) {
        $onlyFilter = strtoupper(substr($arg, 7));
    }
    if (str_starts_with($arg, '--id=')) {
        $idFilter = substr($arg, 5);
    }
}

/** kind layout → kunci config master GSheet */
const KIND_CONFIG = [
    'disassembly' => 'disassembly_templates',
    'subassy_disassembly' => 'subassy_disassembly_templates',
    'inspection' => 'measurement_templates',
];

$webappUrl = config('checksheet_gsheets.webapp_url');
if (!$webappUrl) {
    fwrite(STDERR, "GSHEET_COPY_WEBAPP_URL belum di-set di .env\n");
    exit(1);
}

$secret = config('checksheet_gsheets.secret', '');

/** @var array<string, array{ids: list<string>, sheets: array<string, array<string, true>>, labels: list<string>, cell_count: int}> */
$targets = [];

$layouts = SpreadsheetLayout::query()
    ->whereIn('kind', array_keys(KIND_CONFIG))
    ->orderBy('major_category')
    ->orderBy('egi_model')
    ->orderBy('kind')
    ->get();

foreach ($layouts as $layout) {
    if ($layout->part_row_count <= 0 || $layout->decision_map === []) {
        continue;
    }

    $egi = strtoupper(trim((string) $layout->egi_model));
    if ($onlyFilter && !str_contains($egi, $onlyFilter) && !str_contains($layout->major_category, $onlyFilter)) {
        continue;
    }

    $configKey = KIND_CONFIG[$layout->kind];
    $templateId = templateIdFor($configKey, $layout->major_category, $egi);
    if (!$templateId) {
        fwrite(STDERR, "  SKIP (no config ID): {$layout->major_category} {$egi} {$layout->kind}\n");
        continue;
    }

    if ($idFilter && $templateId !== $idFilter) {
        continue;
    }

    if (!isset($targets[$templateId])) {
        $targets[$templateId] = [
            'ids' => [],
            'sheets' => [],
            'labels' => [],
            'cell_count' => 0,
        ];
    }

    $label = "{$layout->major_category} {$egi} ({$layout->kind})";
    $targets[$templateId]['labels'][] = $label;

    foreach ($layout->decision_map as $sheetName => $map) {
        if (!isset($targets[$templateId]['sheets'][$sheetName])) {
            $targets[$templateId]['sheets'][$sheetName] = [];
        }

        foreach ($map['parts'] ?? [] as $part) {
            foreach ($part['cells'] ?? [] as $ref) {
                $ref = strtoupper(trim((string) $ref));
                if ($ref === '') {
                    continue;
                }
                if (!isset($targets[$templateId]['sheets'][$sheetName][$ref])) {
                    $targets[$templateId]['sheets'][$sheetName][$ref] = true;
                    $targets[$templateId]['cell_count']++;
                }
            }
        }
    }
}

if ($targets === []) {
    echo "Tidak ada target. Jalankan: php artisan checksheet:import-layouts\n";
    exit(0);
}

// Urutkan label & ringkas duplikat ID (D375-6 / PC1250-8 share master yang sama)
foreach ($targets as $id => &$target) {
    $target['labels'] = array_values(array_unique($target['labels']));
    sort($target['labels']);
}
unset($target);

echo ($dryRun ? 'DRY RUN — ' : '') . count($targets) . " master spreadsheet, "
    . array_sum(array_column($targets, 'cell_count')) . " sel keputusan\n";
echo str_repeat('-', 80) . "\n";

$ok = 0;
$failed = 0;

foreach ($targets as $spreadsheetId => $target) {
    $sheetPayload = [];
    foreach ($target['sheets'] as $name => $refs) {
        $cells = array_keys($refs);
        sort($cells);
        $sheetPayload[] = ['name' => $name, 'cells' => $cells];
    }

    usort($sheetPayload, fn ($a, $b) => strcmp($a['name'], $b['name']));

    $labelLine = implode('; ', $target['labels']);
    echo "\n[{$spreadsheetId}]\n  {$labelLine}\n";
    echo "  {$target['cell_count']} sel, " . count($sheetPayload) . " tab\n";
    foreach ($sheetPayload as $sp) {
        echo "    · {$sp['name']}: " . count($sp['cells']) . " checkbox\n";
    }

    if ($dryRun) {
        $ok++;
        continue;
    }

    $payload = [
        'action' => 'apply_checkboxes',
        'spreadsheet_id' => $spreadsheetId,
        'dry_run' => false,
        'sheets' => $sheetPayload,
        'secret' => $secret,
    ];

    try {
        $response = postToAppsScriptWithRetry($webappUrl, $payload, 120, 3);
    } catch (\Illuminate\Http\Client\ConnectionException $e) {
        $failed++;
        echo "  GAGAL timeout/koneksi: {$e->getMessage()}\n";
        echo "         (GAS mungkin masih jalan — cek master di browser, lalu ulangi dengan --only=)\n";
        continue;
    }

    $data = $response->json();

    if (!$response->successful() || !($data['ok'] ?? false)) {
        $failed++;
        $err = $data['error'] ?? $response->body();
        if (is_string($err) && str_contains($err, 'SpreadsheetApp.openById')) {
            echo "  GAGAL: Apps Script belum di-authorize untuk Spreadsheet.\n";
            echo "         → Apps Script editor → Run authorizeSpreadsheetAccess → Allow\n";
            echo "         → Deploy New version → ulangi php tools/apply_master_checkboxes.php --apply\n";
        } else {
            echo "  GAGAL HTTP {$response->status()}: {$err}\n";
        }
        continue;
    }

    $report = $data['report'] ?? [];
    echo "  OK — {$report['title']} — applied: " . ($report['applied'] ?? '?')
        . ', skipped: ' . ($report['skipped'] ?? 0) . "\n";

    foreach ($report['errors'] ?? [] as $err) {
        echo "    ! {$err}\n";
    }

    $ok++;
    // Jeda singkat supaya tidak kena quota GAS
    usleep(500000);
}

echo "\n" . str_repeat('-', 80) . "\n";
echo ($dryRun ? 'DRY RUN selesai. Jalankan dengan --apply setelah deploy Apps Script.' : "Selesai: OK {$ok}, gagal {$failed}") . "\n";

exit($failed > 0 ? 1 : 0);

/**
 * @param  array<string, mixed>  $payload
 */
function postToAppsScriptWithRetry(string $url, array $payload, int $timeout, int $attempts): \Illuminate\Http\Client\Response
{
    $last = null;
    for ($i = 1; $i <= $attempts; $i++) {
        try {
            $last = postToAppsScript($url, $payload, $timeout);
            return $last;
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            if ($i >= $attempts) {
                throw $e;
            }
            fwrite(STDERR, "  retry {$i}/{$attempts} (timeout)...\n");
            sleep(3);
        }
    }

    throw new \RuntimeException('postToAppsScriptWithRetry: unreachable');
}

function templateIdFor(string $configKey, string $category, string $egi): ?string
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

    if (!$id && $egi === 'D375') {
        $id = config("checksheet_gsheets.{$configKey}.{$category}.D375-6")
            ?: config("checksheet_gsheets.{$configKey}.{$category}.D375");
    }

    if (!$id && $egi === 'D375-6') {
        $id = config("checksheet_gsheets.{$configKey}.{$category}.D375-6");
    }

    if (!$id && $egi === 'D155-6') {
        $id = config("checksheet_gsheets.{$configKey}.{$category}.D155-6");
    }

    return $id ?: null;
}
