<?php
/**
 * Format master GSheet: kotak keputusan per part —
 * merge vertikal + checkbox + alignment center, dalam satu operasi.
 *
 * Hanya menyentuh kolom REUSE/SALVAGE/REPLACE (Engine/Disassembly) atau
 * U/A · U/R · R/N (Inspection CV). Kolom NO dan PART NAME tidak disentuh.
 *
 * Part yang punya panduan posisi RH/LH/FRONT/CENTRE/REAR: kotak keputusan
 * dimulai tepat DI BAWAH grid panduan itu.
 *
 *   php tools/format_master_gsheets.php --dry-run
 *   php tools/format_master_gsheets.php --apply
 *   php tools/format_master_gsheets.php --apply --only=D155
 *   php tools/format_master_gsheets.php --apply --id=<spreadsheet id>
 *   php tools/format_master_gsheets.php --apply --checkbox-only   ← tanpa merge vertikal
 *
 * WAJIB: deploy tools/gsheet_copy_webapp.gs + Run authorizeSpreadsheetAccess
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
require __DIR__ . '/master_gsheet_format_lib.php';

/** @var list<string> */
$argv = $_SERVER['argv'] ?? [];

$dryRun = !in_array('--apply', $argv, true);
$onlyFilter = null;
$idFilter = null;
$doMerge = !in_array('--checkbox-only', $argv, true);

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--only=')) {
        $onlyFilter = strtoupper(substr($arg, 7));
    }
    if (str_starts_with($arg, '--id=')) {
        $idFilter = substr($arg, 5);
    }
}

$webappUrl = config('checksheet_gsheets.webapp_url');
if (!$webappUrl) {
    fwrite(STDERR, "GSHEET_COPY_WEBAPP_URL belum di-set di .env\n");
    exit(1);
}

$secret = config('checksheet_gsheets.secret', '');

// Hanya layout dengan kolom keputusan FR — measurement Engine tidak disentuh.
$formatKinds = ['disassembly', 'subassy_disassembly', 'inspection'];

$layouts = App\Models\SpreadsheetLayout::query()
    ->whereIn('kind', $formatKinds)
    ->orderBy('major_category')
    ->orderBy('egi_model')
    ->orderBy('kind')
    ->get();

$targets = masterGsheetBuildTargets($layouts, $onlyFilter, $idFilter);

if ($targets === []) {
    echo "Tidak ada target. Jalankan: php artisan checksheet:import-layouts\n";
    exit(0);
}

$totalBoxes = 0;
$totalMerged = 0;
foreach ($targets as $target) {
    foreach ($target['sheets'] as $sheet) {
        foreach ($sheet['boxes'] as $box) {
            $totalBoxes++;
            if ($box['endRow'] > $box['startRow']) {
                $totalMerged++;
            }
        }
    }
}

echo ($dryRun ? 'DRY RUN — ' : '') . count($targets) . " master spreadsheet\n";
echo "  kotak keputusan: {$totalBoxes} (checkbox + center)\n";
echo '  merge vertikal : ' . ($doMerge ? "{$totalMerged} range" : 'skip') . "\n";
echo str_repeat('-', 80) . "\n";

$ok = 0;
$failed = 0;

foreach ($targets as $spreadsheetId => $target) {
    $labelLine = implode('; ', $target['labels']);
    echo "\n[{$spreadsheetId}]\n  {$labelLine}\n";

    foreach ($target['sheets'] as $name => $sheet) {
        $bc = count($sheet['boxes']);
        if ($bc) {
            echo "    · {$name}: {$bc} kotak\n";
        }
    }

    if ($dryRun) {
        $ok++;
        continue;
    }

    $sheetFailed = false;

    foreach ($target['sheets'] as $name => $sheet) {
        if ($sheet['boxes'] === []) {
            continue;
        }

        $boxes = [];
        foreach ($sheet['boxes'] as $box) {
            $boxes[] = [
                'col' => $box['col'],
                'startRow' => $box['startRow'],
                'endRow' => $doMerge ? $box['endRow'] : $box['startRow'],
            ];
        }

        $clear = $sheet['clear'] ?? [];
        sort($clear);

        $applied = 0;
        $errors = [];

        foreach (array_chunk($boxes, 15) as $ci => $chunk) {
            try {
                $response = masterGsheetPostWithRetry($webappUrl, [
                    'action' => 'apply_decision_boxes',
                    'spreadsheet_id' => $spreadsheetId,
                    'dry_run' => false,
                    'sheets' => [[
                        'name' => $name,
                        'boxes' => $chunk,
                        // Bersihkan sisa checkbox lama sekali di batch pertama.
                        'clear_cells' => $ci === 0 ? $clear : [],
                    ]],
                    'secret' => $secret,
                ], 180, 3);
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                $failed++;
                $sheetFailed = true;
                echo "  GAGAL timeout: {$e->getMessage()}\n";
                break;
            }

            $data = $response->json();
            if (!$response->successful() || !($data['ok'] ?? false)) {
                $failed++;
                $sheetFailed = true;
                $err = (string) ($data['error'] ?? $response->body());
                if (!masterGsheetPrintAuthHint($err)) {
                    echo '  GAGAL: ' . masterGsheetFormatError($err) . "\n";
                }
                break;
            }

            $report = $data['report'] ?? [];
            $applied += (int) ($report['applied'] ?? 0);
            foreach ($report['errors'] ?? [] as $e) {
                $errors[] = (string) $e;
            }
            usleep(300000);
        }

        if ($sheetFailed) {
            break;
        }

        if ($errors !== []) {
            $failed++;
            $sheetFailed = true;
            echo '  GAGAL — ' . count($errors) . ' kotak bermasalah (' . $name . '), contoh: '
                . masterGsheetFormatError($errors[0]) . "\n";
            break;
        }

        echo "  OK — {$applied} kotak ({$name})\n";
    }

    if ($sheetFailed) {
        continue;
    }

    $ok++;
    usleep(400000);
}

echo "\n" . str_repeat('-', 80) . "\n";
echo ($dryRun ? 'DRY RUN selesai. Jalankan --apply setelah deploy + authorize Apps Script.' : "Selesai: OK {$ok}, gagal {$failed}") . "\n";

exit($failed > 0 ? 1 : 0);
