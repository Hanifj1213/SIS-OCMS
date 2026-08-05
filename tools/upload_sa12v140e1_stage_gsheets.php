<?php
/**
 * Upload template Assembly & Testbench SA12V140E-1 ke Google Sheets via
 * Apps Script web app, lalu isi ID ke config/checksheet_gsheets.php.
 *
 * File Assembly aslinya .xls — konversi dulu ke .xlsx lewat Excel COM
 * (JANGAN openpyxl), default dicari di %TEMP%.
 *
 * Jalankan:
 *   php tools/upload_sa12v140e1_stage_gsheets.php
 *   php tools/upload_sa12v140e1_stage_gsheets.php --dry-run
 */
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

require __DIR__ . '/apps_script_http.php';

$opts = getopt('', ['dry-run', 'force']);
$dryRun = array_key_exists('dry-run', $opts);
$force = array_key_exists('force', $opts);

$webappUrl = config('checksheet_gsheets.webapp_url');
$secret = config('checksheet_gsheets.secret', '');

if (!$webappUrl) {
    fwrite(STDERR, "GSHEET_COPY_WEBAPP_URL belum di-set di .env\n");
    exit(1);
}

$root = realpath(__DIR__ . '/../CHECKSHEET FOR PROCESS DEVELOPMEN ROBBY/ENGINE/SA12V140E-1/MAINLINE');
$assemblyXlsx = getenv('TEMP') . DIRECTORY_SEPARATOR . 'ASSEMBLY ENGINE SA12V140E-1.xlsx';

$queue = [
    [
        'config_key' => 'assembly_templates',
        'path' => $assemblyXlsx,
        'filename' => 'ASSEMBLY ENGINE SA12V140E-1.xlsx',
        'subdir' => 'Engine/SA12V140E-1',
    ],
    [
        'config_key' => 'testbench_templates',
        'path' => $root . '/TESTBENCH/TESTBENCH ENGINE SA12V140E-1.xlsx',
        'filename' => 'TESTBENCH ENGINE SA12V140E-1.xlsx',
        'subdir' => 'Engine/SA12V140E-1',
    ],
];

$configPath = __DIR__ . '/../config/checksheet_gsheets.php';
$config = include $configPath;
$updated = 0;

foreach ($queue as $item) {
    $existing = $config[$item['config_key']]['Engine']['SA12V140E-1'] ?? null;
    if ($existing && !$force) {
        echo "SKIP (sudah ada di config): {$item['filename']} → {$existing}\n";
        continue;
    }

    if (!is_file($item['path'])) {
        fwrite(STDERR, "File tidak ditemukan: {$item['path']}\n");
        exit(1);
    }

    $mb = round(filesize($item['path']) / 1048576, 2);
    echo "UPLOAD {$item['filename']} ({$mb} MB)... ";

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

    $response = postToAppsScript($webappUrl, $payload, 300);
    $data = $response->json();

    if (!$response->successful() || !($data['ok'] ?? false) || empty($data['id'])) {
        echo 'FAIL HTTP ' . $response->status() . ' ' . json_encode($data) . "\n";
        exit(1);
    }

    echo "OK {$data['id']}\n";
    $config[$item['config_key']]['Engine']['SA12V140E-1'] = $data['id'];
    $updated++;
    usleep(400000);
}

if ($dryRun || $updated === 0) {
    exit(0);
}

// Tulis ulang config — webapp_url & secret tetap dari env()
unset($config['webapp_url'], $config['secret']);
$export = var_export($config, true);
$php = <<<PHP
<?php

return array_merge([
    'webapp_url' => env('GSHEET_COPY_WEBAPP_URL'),
    'secret' => env('GSHEET_COPY_SECRET', ''),
], {$export});

PHP;
file_put_contents($configPath, $php);
echo "Config diupdate: {$updated} ID.\nJalankan: php artisan config:clear\n";
