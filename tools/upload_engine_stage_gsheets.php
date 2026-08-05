<?php
/**
 * Upload template Assembly (stage 4) & Testbench (stage 5) engine mainline
 * ke Google Sheets via Apps Script, lalu petakan ID ke config untuk SEMUA
 * EGI unit yang memakai engine tsb.
 *
 * Pemetaan engine → unit:
 *   SA12V140E-1  → WA800-3            (sudah terupload sesi sebelumnya)
 *   SA6D140E-2   → GD825A-2/GD825A    (assembly .doc & testbench TIDAK ADA — dilewati)
 *   SAA12V140E-3 → PC2000-8           (assembly .doc — hanya testbench)
 *   SAA6D140E-5  → D155-6/D155
 *   SAA6D170E-5  → D375-6/D375/PC1250-8
 *
 * Assembly .xls dikonversi dulu ke .xlsx lewat Excel COM (JANGAN openpyxl),
 * hasilnya ditaruh di %TEMP%.
 *
 * Jalankan:
 *   php tools/upload_engine_stage_gsheets.php [--dry-run] [--force]
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

$engineRoot = realpath(__DIR__ . '/../CHECKSHEET FOR PROCESS DEVELOPMEN ROBBY/ENGINE');
$tmp = getenv('TEMP');

// Satu entri per file yang diupload; 'egis' = semua key config yang diisi ID sama.
$queue = [
    [
        'config_key' => 'assembly_templates',
        'model' => 'SAA6D140E-5',
        'egis' => ['SAA6D140E-5', 'D155-6', 'D155'],
        'path' => $tmp . '/ASSEMBLY ENGINE SAA6D140E-5.xlsx',
        'filename' => 'ASSEMBLY ENGINE SAA6D140E-5.xlsx',
    ],
    [
        'config_key' => 'assembly_templates',
        'model' => 'SAA6D170E-5',
        'egis' => ['SAA6D170E-5', 'D375-6', 'D375', 'PC1250-8'],
        'path' => $tmp . '/ASSEMBLY ENGINE SAA6D170E-5.xlsx',
        'filename' => 'ASSEMBLY ENGINE SAA6D170E-5.xlsx',
    ],
    [
        'config_key' => 'testbench_templates',
        'model' => 'SAA12V140E-3',
        'egis' => ['SAA12V140E-3', 'PC2000-8'],
        'path' => $engineRoot . '/SAA12V140E-3/MAIN LINE/TESTBENCH/Egine Perfomance test 12V140-3.xlsx',
        'filename' => 'TESTBENCH ENGINE SAA12V140E-3.xlsx',
    ],
    [
        'config_key' => 'testbench_templates',
        'model' => 'SAA6D140E-5',
        'egis' => ['SAA6D140E-5', 'D155-6', 'D155'],
        'path' => $engineRoot . '/SAA6D140E-5/MAIN LINE/TESTBENCH/TESTBENCH ENGINE SAA6D140E-5.xlsx',
        'filename' => 'TESTBENCH ENGINE SAA6D140E-5.xlsx',
    ],
    [
        'config_key' => 'testbench_templates',
        'model' => 'SAA6D170E-5',
        'egis' => ['SAA6D170E-5', 'D375-6', 'D375', 'PC1250-8'],
        'path' => $engineRoot . '/SAA6D170E-5/MAIN LINE/TESTBENCH/Egine Perfomance test 6170-5.xlsx',
        'filename' => 'TESTBENCH ENGINE SAA6D170E-5.xlsx',
    ],
];

$configPath = __DIR__ . '/../config/checksheet_gsheets.php';
$config = include $configPath;
$updated = 0;

foreach ($queue as $item) {
    $existing = $config[$item['config_key']]['Engine'][$item['model']] ?? null;

    if ($existing && !$force) {
        echo "SKIP (sudah ada): {$item['filename']} → {$existing}\n";
        // Tetap pastikan alias EGI unit terisi
        foreach ($item['egis'] as $egi) {
            if (($config[$item['config_key']]['Engine'][$egi] ?? null) !== $existing) {
                $config[$item['config_key']]['Engine'][$egi] = $existing;
                $updated++;
            }
        }
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
        'subdir' => 'Engine/' . $item['model'],
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
    foreach ($item['egis'] as $egi) {
        $config[$item['config_key']]['Engine'][$egi] = $data['id'];
    }
    $updated++;
    usleep(400000);
}

// Alias EGI unit untuk SA12V140E-1 yang sudah terupload sebelumnya:
// komponen nyata terdaftar dengan EGI unit (WA800-3), bukan model engine.
foreach (['assembly_templates', 'testbench_templates'] as $key) {
    $id = $config[$key]['Engine']['SA12V140E-1'] ?? null;
    if ($id && ($config[$key]['Engine']['WA800-3'] ?? null) !== $id) {
        $config[$key]['Engine']['WA800-3'] = $id;
        $updated++;
    }
}

if ($dryRun || $updated === 0) {
    echo "Tidak ada perubahan config.\n";
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
echo "Config diupdate ({$updated} entri).\nJalankan: php artisan config:clear\n";
