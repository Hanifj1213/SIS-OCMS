<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProductionCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ocms:production-check {--strict : Gagal (exit code 1) jika ada peringatan/warning}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifikasi kesiapan konfigurasi & keamanan produksi SISI-OCMS (Preflight check)';

    /** @var list<array{check: string, result: string, detail: string}> */
    private array $results = [];

    private int $errorCount = 0;

    private int $warningCount = 0;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('');
        $this->info('╔══════════════════════════════════════════════════╗');
        $this->info('║   SISI-OCMS Production Readiness Preflight      ║');
        $this->info('╚══════════════════════════════════════════════════╝');
        $this->newLine();

        $this->checkEnvironment();
        $this->checkDatabase();
        $this->checkQueueCacheSession();
        $this->checkGsheetSecrets();
        $this->checkStorageSymlink();
        $this->checkStoragePermissions();
        $this->checkLogging();

        // --- Summary table ---
        $this->newLine();
        $this->table(
            ['Check', 'Result', 'Detail'],
            array_map(fn (array $r) => [
                $r['check'],
                $r['result'],
                mb_strimwidth($r['detail'], 0, 72, '…'),
            ], $this->results),
        );

        $this->newLine();

        if ($this->errorCount > 0) {
            $this->error("  ❌ Preflight GAGAL — {$this->errorCount} error, {$this->warningCount} warning  ");
            $this->error('  Perbaiki seluruh error sebelum deploy ke produksi.');

            return 1;
        }

        if ($this->option('strict') && $this->warningCount > 0) {
            $this->error("  Strict mode: Preflight GAGAL — {$this->warningCount} warning  ");

            return 1;
        }

        if ($this->warningCount > 0) {
            $this->warn("  ⚠️  Preflight LULUS dengan {$this->warningCount} warning  ");

            return 0;
        }

        $this->info('  ✅ Production Readiness Preflight Check LULUS!  ');

        return 0;
    }

    // ------------------------------------------------------------------
    // Individual checks
    // ------------------------------------------------------------------

    private function checkEnvironment(): void
    {
        $env = config('app.env');
        $debug = config('app.debug');
        $key = config('app.key');
        $url = (string) config('app.url');

        // APP_KEY
        if (empty($key)) {
            $this->addFail('APP_KEY', 'Belum di-generate — jalankan php artisan key:generate');
        } else {
            $this->addPass('APP_KEY', 'Set');
        }

        // APP_ENV + APP_DEBUG
        if ($env === 'production' && $debug) {
            $this->addFail('APP_DEBUG', 'true pada production — stack trace & credential bocor saat exception');
        } else {
            $this->addPass('APP_DEBUG', $debug ? 'true (ok untuk development)' : 'false');
        }

        // APP_URL scheme
        if ($env === 'production' && ! str_starts_with($url, 'https://')) {
            $this->addWarn('APP_URL', "Belum HTTPS ({$url}). WebAuthn/Passkey dan secure cookie memerlukan HTTPS.");
        } elseif ($env === 'production') {
            $this->addPass('APP_URL', $url);
        } else {
            $this->addPass('APP_URL', "{$url} (non-production, ok)");
        }
    }

    private function checkDatabase(): void
    {
        $driver = config('database.default');
        $env = config('app.env');

        // Connection test
        try {
            DB::connection()->getPdo();
            $this->addPass('DB Connection', "Connected ({$driver})");
        } catch (\Throwable $e) {
            $this->addFail('DB Connection', 'Gagal: '.$e->getMessage());

            return; // Skip further DB checks
        }

        // SQLite in production
        if ($driver === 'sqlite' && $env === 'production') {
            $this->addFail('DB Driver', 'SQLite di production — multiuser concurrent locking tidak aman, gunakan MySQL');
        }

        // Root without password (MySQL)
        if ($driver === 'mysql') {
            $dbUser = config('database.connections.mysql.username');
            $dbPass = config('database.connections.mysql.password');

            if ($dbUser === 'root' && empty($dbPass)) {
                $this->addFail('DB Security', 'MySQL user root tanpa password!');
            } elseif ($dbUser === 'root') {
                $this->addWarn('DB Security', 'MySQL user root — disarankan user khusus OCMS');
            } else {
                $this->addPass('DB Security', "User: {$dbUser}");
            }
        }

        // Required tables
        $requiredTables = ['components', 'overhaul_logs', 'users', 'jobs', 'cache', 'sessions'];
        $missing = [];
        foreach ($requiredTables as $table) {
            if (! Schema::hasTable($table)) {
                $missing[] = $table;
            }
        }

        if (! empty($missing)) {
            $this->addFail('DB Tables', 'Tabel wajib belum ada: '.implode(', ', $missing).' — jalankan php artisan migrate');
        } else {
            $this->addPass('DB Tables', count($requiredTables).' tabel wajib tersedia');
        }
    }

    private function checkQueueCacheSession(): void
    {
        $queueConn = config('queue.default');
        $cacheStore = config('cache.default');
        $sessionDriver = config('session.driver');
        $sessionEncrypt = config('session.encrypt');
        $env = config('app.env');

        // Queue
        $this->addPass('Queue', $queueConn);

        // Cache
        try {
            Cache::put('ocms_preflight_test', true, 10);
            if (Cache::get('ocms_preflight_test')) {
                $this->addPass('Cache', "{$cacheStore} — read/write ok");
            } else {
                $this->addFail('Cache', 'Write succeeded but read returned null');
            }
        } catch (\Throwable $e) {
            $this->addFail('Cache', 'Gagal: '.$e->getMessage());
        }

        // Session driver
        if ($env === 'production' && $sessionDriver === 'file') {
            $this->addWarn('Session Driver', 'file — race condition mungkin di multi-worker, disarankan database/redis');
        } else {
            $this->addPass('Session Driver', $sessionDriver);
        }

        // Session encrypt
        if ($env === 'production' && ! $sessionEncrypt) {
            $this->addWarn('Session Encrypt', 'false — disarankan true di produksi');
        } else {
            $this->addPass('Session Encrypt', $sessionEncrypt ? 'true' : 'false (non-production)');
        }
    }

    private function checkGsheetSecrets(): void
    {
        $gsheetUrl = config('checksheet_gsheets.webapp_url');
        $gsheetSecret = config('checksheet_gsheets.secret');

        if (empty($gsheetUrl)) {
            $this->addWarn('GSheet URL', 'GSHEET_COPY_WEBAPP_URL kosong — integrasi GSheet tidak aktif');
        } else {
            $this->addPass('GSheet URL', 'Set');
        }

        if (empty($gsheetSecret)) {
            $this->addFail('GSheet Secret', 'GSHEET_COPY_SECRET kosong — integrasi GSheet fail-closed, ditolak demi keamanan');
        } else {
            $this->addPass('GSheet Secret', 'Set (tidak ditampilkan)');
        }
    }

    private function checkStorageSymlink(): void
    {
        $link = public_path('storage');

        if (! file_exists($link)) {
            $this->addFail('Storage Symlink', 'public/storage tidak ada — jalankan php artisan storage:link');

            return;
        }

        // Windows junctions: PHP is_link() dan is_dir() keduanya false,
        // tapi file_exists() true. Cek apakah target bisa diakses.
        $target = storage_path('app/public');
        if (is_link($link)) {
            $resolved = readlink($link);
            $this->addPass('Storage Symlink', "→ {$resolved}");
        } elseif (is_dir($link)) {
            $this->addPass('Storage Symlink', "→ {$target} (directory)");
        } elseif (is_dir($target)) {
            // Windows junction: file_exists true tapi is_link/is_dir false
            $this->addPass('Storage Symlink', "→ {$target} (junction)");
        } else {
            $this->addFail('Storage Symlink', 'public/storage ada tapi target tidak dapat diakses');
        }
    }

    private function checkStoragePermissions(): void
    {
        $paths = [
            'storage/app' => storage_path('app'),
            'storage/framework/cache' => storage_path('framework/cache'),
            'storage/framework/sessions' => storage_path('framework/sessions'),
            'storage/framework/views' => storage_path('framework/views'),
            'storage/logs' => storage_path('logs'),
            'bootstrap/cache' => base_path('bootstrap/cache'),
        ];

        $unwritable = [];
        foreach ($paths as $label => $path) {
            if (! is_dir($path) || ! is_writable($path)) {
                $unwritable[] = $label;
            }
        }

        if (! empty($unwritable)) {
            $this->addFail('Permissions', 'Folder tidak dapat ditulis: '.implode(', ', $unwritable));
        } else {
            $this->addPass('Permissions', count($paths).' folder writable');
        }
    }

    private function checkLogging(): void
    {
        $logLevel = (string) config('logging.channels.single.level', 'debug');
        $logStack = (string) env('LOG_STACK', 'single');
        $env = config('app.env');

        if ($env === 'production' && in_array($logLevel, ['debug', 'info'], true)) {
            $this->addWarn('LOG_LEVEL', "{$logLevel} — terlalu verbose untuk produksi, disarankan warning");
        } else {
            $this->addPass('LOG_LEVEL', $logLevel);
        }

        if ($env === 'production' && $logStack === 'single') {
            $this->addWarn('LOG_STACK', 'single — tidak rotate, disarankan daily');
        } else {
            $this->addPass('LOG_STACK', $logStack);
        }
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function addPass(string $check, string $detail): void
    {
        $this->results[] = ['check' => $check, 'result' => '✅ PASS', 'detail' => $detail];
    }

    private function addWarn(string $check, string $detail): void
    {
        $this->warningCount++;
        $this->results[] = ['check' => $check, 'result' => '⚠️  WARN', 'detail' => $detail];
    }

    private function addFail(string $check, string $detail): void
    {
        $this->errorCount++;
        $this->results[] = ['check' => $check, 'result' => '❌ FAIL', 'detail' => $detail];
    }
}
