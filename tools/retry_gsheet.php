<?php
// Coba ulang duplikasi GSheet (disassembly + measurement) untuk komponen
// Engine yang salinannya belum lengkap. Jalankan: php tools/retry_gsheet.php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$svc = new App\Services\ChecksheetGsheetService();

$targets = App\Models\Component::where('major_category', 'Engine')->get();

foreach ($targets as $c) {
    if (!$svc->hasPendingDuplication($c)) {
        continue;
    }

    $svc->duplicateForComponent($c);
    $c->refresh();

    echo "comp {$c->comp_id} ({$c->egi}, SN {$c->serial_number}):" . PHP_EOL;
    echo "  disassembly : " . ($c->gsheet_url ?: 'belum (cek template/log)') . PHP_EOL;
    echo "  measurement : " . ($c->gsheet_measurement_url ?: 'belum (cek template/log)') . PHP_EOL;
}

echo "selesai" . PHP_EOL;
