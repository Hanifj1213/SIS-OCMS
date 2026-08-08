<?php
/**
 * Upload otomatis Excel Powertrain (_SIAP_UPLOAD_GSHEET) ke Google Sheets
 * via Apps Script web app, lalu isi ID ke config + simpan manifest.
 *
 * Prasyarat:
 * 1. Update & redeploy tools/gsheet_copy_webapp.gs (aktifkan Drive API advanced service)
 * 2. GSHEET_COPY_WEBAPP_URL di .env sudah benar
 *
 * Jalankan:
 *   php tools/upload_powertrain_gsheets.php
 *   php tools/upload_powertrain_gsheets.php --only=DISASSEMBLY,INSPECTION
 *   php tools/upload_powertrain_gsheets.php --dry-run
 */
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

require __DIR__ . '/apps_script_http.php';

use Illuminate\Support\Facades\Http;

$opts = getopt('', ['only::', 'dry-run', 'force']);
$onlyStages = isset($opts['only'])
    ? array_map('strtoupper', array_filter(array_map('trim', explode(',', $opts['only']))))
    : []; // kosong = semua tahap
$dryRun = array_key_exists('dry-run', $opts);
$force = array_key_exists('force', $opts);

$webappUrl = config('checksheet_gsheets.webapp_url');
$secret = config('checksheet_gsheets.secret', '');

if (!$webappUrl) {
    fwrite(STDERR, "GSHEET_COPY_WEBAPP_URL belum di-set di .env\n");
    exit(1);
}

$root = realpath(__DIR__ . '/../CHECKSHEET FOR PROCESS DEVELOPMEN ROBBY/POWERTRAIN/_SIAP_UPLOAD_GSHEET');
if (!$root) {
    fwrite(STDERR, "Folder _SIAP_UPLOAD_GSHEET Powertrain tidak ditemukan. Jalankan split dulu.\n");
    exit(1);
}

$manifestPath = __DIR__ . '/powertrain_gsheet_manifest.json';
$manifest = file_exists($manifestPath)
    ? (json_decode(file_get_contents($manifestPath), true) ?: [])
    : [];

$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);

$queue = [];
foreach ($files as $file) {
    /** @var SplFileInfo $file */
    if (!$file->isFile() || !preg_match('/\.xlsx$/i', $file->getFilename())) {
        continue;
    }
    if (str_starts_with($file->getFilename(), '~$')) {
        continue;
    }

    $rel = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
    // rel: Control Valve/WA800-3/DISASSEMBLY Control Valve WA800-3.xlsx
    if (!preg_match('#^([^/]+)/([^/]+)/(RECEIVING|DISASSEMBLY|INSPECTION|ASSEMBLY|TEST|DELIVERY)\b#i', $rel, $m)) {
        echo "skip (nama tidak sesuai pola): {$rel}\n";
        continue;
    }

    $category = $m[1];
    $egi = strtoupper($m[2]);
    $stage = strtoupper($m[3]);

    if ($onlyStages && !in_array($stage, $onlyStages, true)) {
        continue;
    }

    $queue[] = [
        'path' => $file->getPathname(),
        'rel' => $rel,
        'category' => $category,
        'egi' => $egi,
        'stage' => $stage,
        'filename' => $file->getFilename(),
        'subdir' => $category . '/' . $egi,
        'bytes' => $file->getSize(),
    ];
}

usort($queue, fn ($a, $b) => strcmp($a['rel'], $b['rel']));

echo 'Antrian: ' . count($queue) . ' file' . ($dryRun ? ' (dry-run)' : '') . PHP_EOL;
echo 'Webapp: ' . $webappUrl . PHP_EOL;

$ok = 0;
$skip = 0;
$fail = 0;

foreach ($queue as $i => $item) {
    $n = $i + 1;
    $key = $item['rel'];

    if (!$force && !empty($manifest[$key]['id'])) {
        echo "[{$n}/" . count($queue) . "] SKIP (sudah): {$key}\n";
        $skip++;
        continue;
    }

    echo "[{$n}/" . count($queue) . "] UPLOAD {$key} (" . round($item['bytes'] / 1048576, 2) . " MB)... ";

    if ($dryRun) {
        echo "dry-run\n";
        continue;
    }

    $payload = [
        'action' => 'upload',
        'filename' => $item['filename'],
        'subdir' => $item['subdir'],
        'data' => base64_encode(file_get_contents($item['path'])),
        'secret' => $secret,
    ];

    try {
        $response = postToAppsScript($webappUrl, $payload, 180);
        $data = $response->json();

        if ($response->successful() && ($data['ok'] ?? false) && !empty($data['id'])) {
            $manifest[$key] = [
                'id' => $data['id'],
                'url' => $data['url'] ?? ('https://docs.google.com/spreadsheets/d/' . $data['id'] . '/edit'),
                'category' => $item['category'],
                'egi' => $item['egi'],
                'stage' => $item['stage'],
                'uploaded_at' => date('c'),
            ];
            file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            echo "OK {$data['id']}\n";
            $ok++;
            usleep(400000);
        } else {
            $hint = '';
            if ($response->status() === 401 || $response->status() === 403) {
                $hint = ' → Redeploy Web App: Who has access = Anyone';
            } elseif ($response->status() === 405) {
                $hint = ' → HTTP 405 biasanya redirect GAS; pastikan tools/apps_script_http.php terpakai & URL /exec benar';
            } elseif ($data === null) {
                $snippet = preg_replace('/\s+/', ' ', substr($response->body(), 0, 180));
                $hint = " → body bukan JSON (HTTP {$response->status()}): {$snippet}";
            } else {
                $err = $data['error'] ?? json_encode($data);
                if (is_string($err) && str_contains($err, 'Drive')) {
                    $hint = " → {$err} | Aktifkan Services → Drive API di Apps Script";
                } else {
                    $hint = ' → ' . (is_string($err) ? $err : json_encode($data));
                }
            }
            echo "FAIL HTTP {$response->status()}{$hint}\n";
            $fail++;
        }
    } catch (Throwable $e) {
        echo 'ERROR ' . $e->getMessage() . "\n";
        $fail++;
    }
}

echo PHP_EOL . "Selesai upload: ok={$ok} skip={$skip} fail={$fail}\n";
echo "Manifest: {$manifestPath}\n";

if ($dryRun) {
    exit(0);
}

// Isi config untuk DISASSEMBLY + INSPECTION (stage 2), ASSEMBLY (stage 4)
// dan TEST (stage 5) tanpa menimpa env()
$configPath = __DIR__ . '/../config/checksheet_gsheets.php';
$config = include $configPath;

$stageMap = [
    'DISASSEMBLY' => 'disassembly_templates',
    'INSPECTION' => 'measurement_templates',
    'ASSEMBLY' => 'assembly_templates',
    'TEST' => 'testbench_templates',
];

$updated = 0;
foreach ($manifest as $entry) {
    $stage = $entry['stage'] ?? '';
    if (!isset($stageMap[$stage])) {
        continue;
    }
    $cfgKey = $stageMap[$stage];
    $cat = $entry['category'];
    $egi = $entry['egi'];
    $id = $entry['id'];

    if (!isset($config[$cfgKey]) || !is_array($config[$cfgKey])) {
        $config[$cfgKey] = [];
    }
    if (!isset($config[$cfgKey][$cat]) || !is_array($config[$cfgKey][$cat])) {
        $config[$cfgKey][$cat] = [];
    }
    $prev = $config[$cfgKey][$cat][$egi] ?? '';
    if ($prev !== $id) {
        $config[$cfgKey][$cat][$egi] = $id;
        $updated++;
        if ($egi === 'GD825A-2') {
            $config[$cfgKey][$cat]['GD825A'] = $id;
        }
    }
}

writeChecksheetConfig($configPath, $config);
echo "Config diupdate: {$updated} ID (DISASSEMBLY/INSPECTION).\n";
echo "Jalankan: php artisan config:clear\n";

/**
 * Tulis ulang config, TAPI webapp_url & secret tetap dari env() —
 * jangan hardcode URL biar .env tetap berlaku.
 */
function writeChecksheetConfig(string $path, array $config): void
{
    unset($config['webapp_url'], $config['secret']);

    $export = var_export($config, true);
    $php = <<<PHP
<?php

return array_merge([
    'webapp_url' => env('GSHEET_COPY_WEBAPP_URL'),
    'secret' => env('GSHEET_COPY_SECRET', ''),
], {$export});

PHP;
    file_put_contents($path, $php);
}
