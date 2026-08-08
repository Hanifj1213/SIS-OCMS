<?php
/**
 * Pindahkan data dari SQLite development ke MySQL (Laragon).
 *
 * Prasyarat:
 *   1. MySQL Laragon sudah Start
 *   2. Database `sisi_ocms` sudah dibuat
 *   3. .env sudah di-set ke DB_CONNECTION=mysql
 *
 * Jalankan:
 *   php artisan migrate --force
 *   php tools/migrate_sqlite_to_mysql.php "C:/path/to/database.sqlite"
 */
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$sqlitePath = $argv[1] ?? null;
if (!$sqlitePath || !is_file($sqlitePath)) {
    fwrite(STDERR, "Usage: php tools/migrate_sqlite_to_mysql.php <path-to-database.sqlite>\n");
    exit(1);
}

if (config('database.default') !== 'mysql') {
    fwrite(STDERR, "Set DB_CONNECTION=mysql di .env dulu.\n");
    exit(1);
}

Config::set('database.connections.sqlite_import', [
    'driver' => 'sqlite',
    'database' => $sqlitePath,
    'prefix' => '',
    'foreign_key_constraints' => false,
]);

$src = DB::connection('sqlite_import');
$dst = DB::connection('mysql');

try {
    $dst->getPdo();
} catch (Throwable $e) {
    fwrite(STDERR, "MySQL tidak bisa dihubungi: {$e->getMessage()}\n");
    fwrite(STDERR, "Pastikan MySQL Laragon sudah Start dan database sisi_ocms ada.\n");
    exit(1);
}

$tables = $src->select(
    "SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name"
);
$tableNames = array_map(fn ($r) => $r->name, $tables);

$skip = ['migrations', 'spreadsheet_layouts', 'component_spreadsheet_answers'];
$copy = array_values(array_filter($tableNames, fn ($t) => !in_array($t, $skip, true)));

echo '=== SQLite → MySQL ===' . PHP_EOL;
echo 'Source: ' . $sqlitePath . PHP_EOL;
echo 'Target: ' . config('database.connections.mysql.database') . PHP_EOL;
echo 'Skip: ' . implode(', ', $skip) . ' (layout terkompresi — impor ulang lewat checksheet:import-layouts jika perlu)' . PHP_EOL . PHP_EOL;

$dst->statement('SET FOREIGN_KEY_CHECKS=0');

foreach ($copy as $table) {
    if (!Schema::connection('mysql')->hasTable($table)) {
        echo "[SKIP] {$table} — belum ada di MySQL (jalankan migrate dulu)\n";
        continue;
    }

    try {
        $rows = $src->table($table)->get();
        $dst->table($table)->truncate();

        if ($rows->isEmpty()) {
            echo "[OK] {$table}: 0 baris\n";
            continue;
        }

        $chunkSize = 200;
        $inserted = 0;
        foreach ($rows->chunk($chunkSize) as $chunk) {
            $payload = $chunk->map(fn ($row) => (array) $row)->all();
            $dst->table($table)->insert($payload);
            $inserted += count($payload);
        }

        echo "[OK] {$table}: {$inserted} baris\n";
    } catch (Throwable $e) {
        echo "[FAIL] {$table}: {$e->getMessage()}\n";
    }
}

$dst->statement('SET FOREIGN_KEY_CHECKS=1');

echo PHP_EOL . 'Selesai. Verifikasi:' . PHP_EOL;
echo '  php artisan tinker --execute="echo App\\Models\\Component::count();"' . PHP_EOL;
