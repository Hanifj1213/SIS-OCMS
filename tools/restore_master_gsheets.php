<?php
/**
 * Pulihkan master Google Sheets setelah format_master_gsheets.php merusak sheet.
 *
 * MODE DEFAULT (xlsx): timpa master dari file .xlsx SIAP lokal — ID & URL tetap.
 *   php tools/restore_master_gsheets.php --dry-run
 *   php tools/restore_master_gsheets.php --apply --confirm
 *   php tools/restore_master_gsheets.php --apply --only=D155 --confirm
 *   php tools/restore_master_gsheets.php --apply --format-targets --confirm
 *
 * MODE revision (TIDAK jalan untuk Google Sheets native — hanya --list):
 *   php tools/restore_master_gsheets.php --list
 *
 * WAJIB sebelum --apply:
 * 1. Deploy tools/gsheet_copy_webapp.gs (New version)
 * 2. Run authorizeSpreadsheetAccess → Allow → Deploy New version
 *
 * Sumber xlsx:
 *   CHECKSHEET .../ENGINE/_SIAP_UPLOAD_GSHEET/
 *   CHECKSHEET .../POWERTRAIN/_SIAP_UPLOAD_GSHEET/
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
require __DIR__ . '/master_gsheet_format_lib.php';

/** @var list<string> */
$argv = $_SERVER['argv'] ?? [];

$listOnly = in_array('--list', $argv, true);
$dryRun = !in_array('--apply', $argv, true);
$confirm = in_array('--confirm', $argv, true);
$fromRevision = in_array('--from-revision', $argv, true);
$formatTargetsOnly = in_array('--format-targets', $argv, true);
$onlyFilter = null;
$idFilter = null;
$steps = null;
$before = null;

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--only=')) {
        $onlyFilter = strtoupper(substr($arg, 7));
    }
    if (str_starts_with($arg, '--id=')) {
        $idFilter = substr($arg, 5);
    }
    if (str_starts_with($arg, '--steps=')) {
        $steps = max(1, (int) substr($arg, 8));
    }
    if (str_starts_with($arg, '--before=')) {
        $before = substr($arg, 9);
    }
}

if ($fromRevision && $steps === null && $before === null) {
    $steps = 1;
}

$webappUrl = config('checksheet_gsheets.webapp_url');
if (!$webappUrl) {
    fwrite(STDERR, "GSHEET_COPY_WEBAPP_URL belum di-set di .env\n");
    exit(1);
}

$secret = config('checksheet_gsheets.secret', '');

if ($listOnly || $fromRevision) {
    runRevisionMode(
        $webappUrl,
        $secret,
        $listOnly,
        $dryRun,
        $confirm,
        $onlyFilter,
        $idFilter,
        $steps,
        $before
    );
}

runXlsxRestoreMode(
    $webappUrl,
    $secret,
    $dryRun,
    $confirm,
    $onlyFilter,
    $idFilter,
    $formatTargetsOnly
);

function runXlsxRestoreMode(
    string $webappUrl,
    string $secret,
    bool $dryRun,
    bool $confirm,
    ?string $onlyFilter,
    ?string $idFilter,
    bool $formatTargetsOnly
): never {
    $targets = masterGsheetBuildXlsxRestoreTargets($onlyFilter, $idFilter);

    if ($formatTargetsOnly) {
        $formatKinds = ['disassembly', 'subassy_disassembly', 'inspection'];
        $layouts = App\Models\SpreadsheetLayout::query()
            ->whereIn('kind', $formatKinds)
            ->orderBy('major_category')
            ->orderBy('egi_model')
            ->orderBy('kind')
            ->get();
        $formatTargets = masterGsheetBuildTargets($layouts, $onlyFilter, $idFilter);
        $targets = array_intersect_key($targets, $formatTargets);
    }

    if ($targets === []) {
        echo "Tidak ada target xlsx. Cek folder _SIAP_UPLOAD_GSHEET.\n";
        exit(0);
    }

    echo count($targets) . " master spreadsheet (restore dari .xlsx SIAP"
        . ($formatTargetsOnly ? ', target format saja' : '')
        . ")\n";
    echo str_repeat('-', 80) . "\n";

    if (!$dryRun && !$confirm) {
        echo "\nPERINGATAN: --apply akan MENIMPA isi master di Drive dari file .xlsx lokal.\n";
        echo "ID spreadsheet tetap sama. Jalankan --dry-run dulu.\n";
        echo "Tambahkan --confirm jika sudah yakin.\n";
        exit(1);
    }

    $ok = 0;
    $failed = 0;

    foreach ($targets as $spreadsheetId => $target) {
        $labelLine = implode('; ', $target['labels']);
        $xlsx = $target['xlsx'];
        $size = is_file($xlsx) ? filesize($xlsx) : 0;

        echo "\n[{$spreadsheetId}]\n  {$labelLine}\n";
        echo '  xlsx: ' . basename($xlsx) . ' (' . number_format($size) . " bytes)\n";

        if (!is_file($xlsx)) {
            $failed++;
            echo "  GAGAL: file xlsx tidak ditemukan\n";
            continue;
        }

        $payload = [
            'action' => 'restore_from_xlsx',
            'spreadsheet_id' => $spreadsheetId,
            'filename' => $target['xlsx_name'],
            'data' => base64_encode((string) file_get_contents($xlsx)),
            'dry_run' => $dryRun,
            'secret' => $secret,
        ];

        try {
            $response = masterGsheetPostWithRetry($webappUrl, $payload, 300, 3);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $failed++;
            echo "  GAGAL timeout: {$e->getMessage()}\n";
            continue;
        }

        $data = $response->json();
        if (!$response->successful() || !($data['ok'] ?? false)) {
            $failed++;
            $err = (string) ($data['error'] ?? $response->body());
            echo '  GAGAL: ' . masterGsheetFormatError($err) . "\n";
            if (str_contains($err, 'Drive API')) {
                echo "         → Apps Script: Services → Drive API → Add → Deploy New version\n";
            }
            continue;
        }

        $report = $data['report'] ?? [];
        $title = $report['title'] ?? '?';

        if ($dryRun) {
            echo "  DRY RUN OK — {$title}\n";
        } else {
            echo "  RESTORED — {$title}\n";
            echo '    modified: ' . ($report['modified_time'] ?? '?') . "\n";
        }

        $ok++;
        usleep(800000);
    }

    echo "\n" . str_repeat('-', 80) . "\n";
    echo ($dryRun ? 'DRY RUN' : 'RESTORE') . " selesai: OK {$ok}, gagal {$failed}\n";

    if ($dryRun) {
        echo "\nJalankan restore:\n  php tools/restore_master_gsheets.php --apply --confirm\n";
    }

    exit($failed > 0 ? 1 : 0);
}

function runRevisionMode(
    string $webappUrl,
    string $secret,
    bool $listOnly,
    bool $dryRun,
    bool $confirm,
    ?string $onlyFilter,
    ?string $idFilter,
    ?int $steps,
    ?string $before
): never {
    $targets = masterGsheetCollectTemplateIds($onlyFilter, $idFilter);

    if ($targets === []) {
        echo "Tidak ada master template di config.\n";
        exit(0);
    }

    echo count($targets) . " master spreadsheet unik\n";
    echo "  mode: revision (Google Sheets native TIDAK bisa di-restore via API)\n";
    if ($before) {
        echo "  filter: sebelum {$before}\n";
    } elseif ($steps !== null) {
        echo "  filter: {$steps} langkah ke belakang\n";
    }
    echo str_repeat('-', 80) . "\n";

    if (!$listOnly && !$dryRun) {
        echo "\nERROR: --from-revision --apply tidak jalan (Drive API 404 untuk Google Sheets).\n";
        echo "Pakai: php tools/restore_master_gsheets.php --apply --confirm\n";
        exit(1);
    }

    $ok = 0;
    $failed = 0;

    foreach ($targets as $spreadsheetId => $target) {
        $labelLine = implode('; ', $target['labels']);
        echo "\n[{$spreadsheetId}]\n  {$labelLine}\n";

        $payload = [
            'action' => $listOnly ? 'list_revisions' : 'restore_revision',
            'spreadsheet_id' => $spreadsheetId,
            'secret' => $secret,
            'dry_run' => true,
        ];
        if (!$listOnly && $before) {
            $payload['before'] = $before;
        } elseif (!$listOnly && $steps !== null) {
            $payload['steps'] = $steps;
        }
        if ($listOnly) {
            $payload['limit'] = 8;
        }

        try {
            $response = masterGsheetPostWithRetry($webappUrl, $payload, 180, 3);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $failed++;
            echo "  GAGAL timeout: {$e->getMessage()}\n";
            continue;
        }

        $data = $response->json();
        if (!$response->successful() || !($data['ok'] ?? false)) {
            $failed++;
            echo '  GAGAL: ' . masterGsheetFormatError((string) ($data['error'] ?? $response->body())) . "\n";
            continue;
        }

        if ($listOnly) {
            echo '  ' . ($data['title'] ?? '?') . ' — modified ' . ($data['modified_time'] ?? '?') . "\n";
            foreach ($data['revisions'] ?? [] as $rev) {
                echo '    [' . ($rev['index'] ?? '?') . '] ' . ($rev['modifiedTime'] ?? '?') . "\n";
            }
        } else {
            echo '  ' . ($data['report']['title'] ?? '?') . " — dry-run revision preview\n";
        }
        $ok++;
    }

    echo "\n" . str_repeat('-', 80) . "\n";
    echo ($listOnly ? 'LIST' : 'DRY RUN') . " selesai: OK {$ok}, gagal {$failed}\n";
    echo "\nUntuk restore sebenarnya:\n  php tools/restore_master_gsheets.php --apply --confirm\n";
    exit($failed > 0 ? 1 : 0);
}
