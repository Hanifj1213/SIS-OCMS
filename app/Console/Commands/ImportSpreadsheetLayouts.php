<?php

namespace App\Console\Commands;

use App\Services\SpreadsheetLayoutImporter;
use Illuminate\Console\Command;
use Throwable;

/**
 * Impor template .xlsx dari folder _SIAP_UPLOAD_GSHEET jadi layout lokal.
 *
 *   php artisan checksheet:import-layouts --dry-run
 *   php artisan checksheet:import-layouts
 *   php artisan checksheet:import-layouts --only=INSPECTION --only="Control Valve"
 */
class ImportSpreadsheetLayouts extends Command
{
    protected $signature = 'checksheet:import-layouts
        {--dry-run : tampilkan rencana impor tanpa menulis ke database}
        {--only=* : hanya file yang path-nya memuat teks ini}
        {--path= : impor satu file .xlsx tertentu}
        {--category= : paksa major_category (untuk --path)}
        {--egi= : paksa egi_model (untuk --path)}
        {--kind= : paksa kind (untuk --path)}';

    protected $description = 'Impor layout checksheet dari file Excel SIAP ke database';

    private const ROOTS = [
        'CHECKSHEET FOR PROCESS DEVELOPMEN ROBBY/ENGINE/_SIAP_UPLOAD_GSHEET',
        'CHECKSHEET FOR PROCESS DEVELOPMEN ROBBY/POWERTRAIN/_SIAP_UPLOAD_GSHEET',
    ];

    public function handle(SpreadsheetLayoutImporter $importer): int
    {
        $targets = $this->option('path')
            ? [$this->targetFromPath($this->option('path'))]
            : $this->discover();

        if ($targets === []) {
            $this->warn('Tidak ada file yang cocok.');

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $ok = $failed = 0;

        foreach ($targets as $t) {
            $label = sprintf(
                '%-18s %-10s %-22s %s',
                $t['major_category'],
                $t['egi_model'] ?? '-',
                $t['kind'],
                basename($t['path'])
            );

            if ($dryRun) {
                $this->line("  [rencana] {$label}");
                $ok++;
                continue;
            }

            try {
                $layout = $importer->import($t['path'], $t);
                $this->info(sprintf(
                    '  OK  %s  (%d tab, %d baris part)',
                    $label,
                    $layout->sheet_count,
                    $layout->part_row_count
                ));
                $ok++;
            } catch (Throwable $e) {
                $this->error("  GAGAL {$label}: {$e->getMessage()}");
                $failed++;
            }
        }

        $this->newLine();
        $this->line(str_repeat('-', 72));
        $this->line(($dryRun ? 'DRY RUN — ' : '') . "berhasil {$ok} | gagal {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /** @return array{path: string, major_category: string, egi_model: ?string, kind: string} */
    private function targetFromPath(string $path): array
    {
        return [
            'path' => $path,
            'major_category' => (string) ($this->option('category') ?: 'Engine'),
            'egi_model' => $this->option('egi') ?: null,
            'kind' => (string) ($this->option('kind') ?: 'disassembly'),
        ];
    }

    /**
     * Tebak kategori/EGI/jenis dari struktur folder & nama file.
     *
     * Engine    : {ROOT}/ENGINE/_SIAP_UPLOAD_GSHEET/DISASSEMBLY ENGINE ... (EGI).xlsx
     * Powertrain: {ROOT}/POWERTRAIN/_SIAP_UPLOAD_GSHEET/{Kategori}/{EGI}/{STAGE} ....xlsx
     *
     * @return list<array{path: string, major_category: string, egi_model: ?string, kind: string}>
     */
    private function discover(): array
    {
        $only = array_map('mb_strtolower', (array) $this->option('only'));
        $out = [];

        foreach (self::ROOTS as $root) {
            $dir = base_path($root);
            if (!is_dir($dir)) {
                continue;
            }

            $isEngine = str_contains($root, '/ENGINE/');

            foreach ($this->xlsxFiles($dir) as $path) {
                $name = basename($path);
                if (str_starts_with($name, '~$')) {
                    continue;
                }

                $kind = $this->kindFor($name);
                if ($kind === null) {
                    continue;   // ASSEMBLY / DELIVERY / RECEIVING / TEST tidak dipakai
                }

                $rel = str_replace('\\', '/', substr($path, strlen(base_path()) + 1));

                if ($only !== []) {
                    $hay = mb_strtolower($rel);
                    $match = false;
                    foreach ($only as $needle) {
                        if ($needle !== '' && str_contains($hay, $needle)) {
                            $match = true;
                            break;
                        }
                    }
                    if (!$match) {
                        continue;
                    }
                }

                if ($isEngine) {
                    // Satu workbook mesin bisa dipakai beberapa unit, mis.
                    // "... SAA6D170E-5 (D375-6 PC1250-8).xlsx" -> D375-6 dan PC1250-8.
                    foreach ($this->egisFromEngineName($name) as $egi) {
                        $out[] = [
                            'path' => $path,
                            'major_category' => 'Engine',
                            'egi_model' => $egi,
                            'kind' => $kind,
                        ];
                    }
                } else {
                    // .../_SIAP_UPLOAD_GSHEET/{Kategori}/{EGI}/{file}
                    $parts = explode('/', $rel);
                    $count = count($parts);
                    $out[] = [
                        'path' => $path,
                        'major_category' => $parts[$count - 3] ?? 'Control Valve',
                        'egi_model' => $parts[$count - 2] ?? null,
                        'kind' => $kind,
                    ];
                }
            }
        }

        return $out;
    }

    /** @return list<string> */
    private function xlsxFiles(string $dir): array
    {
        $files = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) === 'xlsx') {
                $files[] = $file->getPathname();
            }
        }
        sort($files);

        return $files;
    }

    private function kindFor(string $filename): ?string
    {
        $upper = strtoupper($filename);

        return match (true) {
            str_contains($upper, 'SUBASSY DISASSEMBLY') => 'subassy_disassembly',
            str_contains($upper, 'SUBASSY MEASUREMENT') => 'subassy_measurement',
            str_contains($upper, 'DISASSEMBLY') => 'disassembly',
            str_contains($upper, 'INSPECTION') => 'inspection',
            str_contains($upper, 'MEASUREMENT') => 'measurement',
            default => null,
        };
    }

    /**
     * EGI unit ada di dalam kurung — itulah nilai yang dipakai components.egi.
     * Kode mesin (SAA6D170E-5) tidak dipakai untuk pencocokan.
     *
     * "DISASSEMBLY ENGINE SAA6D170E-5 (D375-6 PC1250-8).xlsx" -> ['D375-6', 'PC1250-8']
     *
     * @return list<string|null>
     */
    private function egisFromEngineName(string $filename): array
    {
        if (!preg_match('/\(([^)]+)\)/', $filename, $m)) {
            return [null];
        }

        $egis = preg_split('/[\s,]+/', trim($m[1])) ?: [];
        $egis = array_values(array_filter(array_map('strtoupper', $egis)));

        return $egis === [] ? [null] : $egis;
    }
}
