<?php
/** Tes koneksi Apps Script: php tools/ping_gsheet_webapp.php */
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
require __DIR__ . '/apps_script_http.php';

$url = config('checksheet_gsheets.webapp_url');
echo "URL: {$url}\n";

$r = postToAppsScript($url, [
    'action' => 'ping',
    'secret' => config('checksheet_gsheets.secret', ''),
], 30);

echo 'HTTP ' . $r->status() . "\n";
echo $r->body() . "\n";

$data = $r->json();
if (($data['ok'] ?? false) && ($data['ping'] ?? false)) {
    if (!empty($data['driveCreate']) || !empty($data['driveInsert'])) {
        echo "OK — webapp & Drive API siap"
            . (!empty($data['driveCreate']) ? ' (v3 create)' : ' (v2 insert)')
            . ".\n";
        exit(0);
    }
    echo "OK ping, TAPI Drive.Files.create/insert belum ada. Services → Drive API → Add, New version deploy.\n";
    exit(1);
}

echo "GAGAL — pastikan:\n";
echo "1. Script terbaru (ada action ping) sudah di-deploy (New version)\n";
echo "2. Who has access = Anyone\n";
echo "3. .env GSHEET_COPY_WEBAPP_URL = URL /exec dari deployment itu\n";
exit(1);
