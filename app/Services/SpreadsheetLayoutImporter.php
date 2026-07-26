<?php

namespace App\Services;

use App\Models\SpreadsheetLayout;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Impor satu file .xlsx jadi layout yang bisa dirender lokal.
 *
 * Pencarian keyword kolom keputusan (REUSE/SALVAGE/REPLACE atau U/A|U/R|R/N)
 * dikerjakan SEKALI di sini, hasilnya disimpan sebagai decision_map. Setelah
 * itu pengisian dan pembuatan FR tidak perlu menebak posisi kolom lagi —
 * inilah yang membuat template tidak perlu diseragamkan manual.
 */
class SpreadsheetLayoutImporter
{
    /**
     * Kata kunci tab per jenis checksheet; null = ambil semua tab.
     *
     * Workbook Measurement memberi nama tab per part (CRANKSHAFT, CAMSHAFT,
     * ACC DRIVE MEASURE, ...) sehingga tidak ada kata kunci yang seragam —
     * dan memang semua tabnya berisi data ukuran yang perlu ditampilkan.
     */
    public const SHEET_KEYWORDS = [
        'disassembly' => ['disassy', 'diss', 'disassembly', 'engine'],
        'subassy_disassembly' => ['disassy', 'diss', 'disassembly'],
        'inspection' => ['inspeksi', 'inspection'],
        'measurement' => null,
        'subassy_measurement' => null,
    ];

    public function __construct(private readonly XlsxLayoutReader $reader) {}

    /**
     * @param  array{major_category: string, egi_model: ?string, kind: string}  $meta
     */
    public function import(string $xlsxPath, array $meta): SpreadsheetLayout
    {
        $kind = $meta['kind'];
        $keywords = array_key_exists($kind, self::SHEET_KEYWORDS)
            ? self::SHEET_KEYWORDS[$kind]
            : null;

        $layout = $this->reader->read($xlsxPath, $keywords);

        // Jangan sampai gagal total hanya karena penamaan tab tak terduga —
        // lebih baik impor semua tab daripada tidak ada sama sekali.
        if ($layout['sheets'] === [] && $keywords !== null) {
            $layout = $this->reader->read($xlsxPath, null);
        }

        if ($layout['sheets'] === []) {
            throw new RuntimeException('Tidak ada tab terbaca di ' . basename($xlsxPath));
        }

        $slug = $this->slug($meta);
        $decisionMap = [];
        $partRows = 0;

        foreach ($layout['sheets'] as $i => $sheet) {
            $layout['sheets'][$i]['images'] = $this->storeImages($xlsxPath, $sheet['images'] ?? [], $slug);

            $map = $this->detectDecisions($sheet);
            if ($map !== null) {
                $decisionMap[$sheet['name']] = $map;
                $partRows += count($map['parts']);
            }
        }

        return SpreadsheetLayout::updateOrCreate(
            [
                'major_category' => $meta['major_category'],
                'egi_model' => $meta['egi_model'],
                'kind' => $kind,
            ],
            [
                'source_file' => basename($xlsxPath),
                'layout' => $layout,
                'decision_map' => $decisionMap,
                'sheet_count' => count($layout['sheets']),
                'part_row_count' => $partRows,
                'imported_at' => now(),
            ]
        );
    }

    /** @param array{major_category: string, egi_model: ?string, kind: string} $meta */
    private function slug(array $meta): string
    {
        return Str::slug($meta['major_category'] . '-' . ($meta['egi_model'] ?: 'generic') . '-' . $meta['kind']);
    }

    /**
     * Salin gambar dari dalam xlsx ke public/ dan ganti path-nya jadi URL.
     *
     * @param  list<array<string, mixed>>  $images
     * @return list<array<string, mixed>>
     */
    private function storeImages(string $xlsxPath, array $images, string $slug): array
    {
        if ($images === []) {
            return [];
        }

        $dir = public_path("checksheet-media/{$slug}");
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException("Gagal membuat folder gambar: {$dir}");
        }

        $out = [];
        foreach ($images as $image) {
            $name = basename($image['media']);

            // EMF/WMF tidak bisa ditampilkan browser — dicatat supaya renderer
            // bisa menampilkan placeholder, bukan gambar rusak.
            if (!empty($image['vector'])) {
                $image['url'] = null;
                $out[] = $image;
                continue;
            }

            $dest = $dir . DIRECTORY_SEPARATOR . $name;
            if (!is_file($dest)) {
                $data = $this->reader->extractMedia($xlsxPath, $image['media']);
                if ($data === null) {
                    continue;
                }
                file_put_contents($dest, $data);
            }

            $image['url'] = asset("checksheet-media/{$slug}/{$name}");
            $out[] = $image;
        }

        return $out;
    }

    /**
     * Cari baris header keputusan dan baris part di bawahnya.
     *
     * @param  array<string, mixed>  $sheet
     * @return array<string, mixed>|null
     */
    private function detectDecisions(array $sheet): ?array
    {
        $grid = $this->grid($sheet);
        if ($grid === []) {
            return null;
        }

        $headers = [];
        foreach (array_keys($grid) as $r) {
            $header = $this->detectHeaderRow($grid, $r);
            if ($header !== null) {
                $headers[] = $header;
            }
        }

        if ($headers === []) {
            return null;
        }

        $parts = [];
        foreach ($headers as $i => $header) {
            $endRow = $headers[$i + 1]['row'] ?? ($sheet['max_row'] ?? 0);

            for ($r = $header['data_start']; $r < $endRow + 1; $r++) {
                $no = $grid[$r][$header['no_col']] ?? null;
                $name = $grid[$r][$header['name_col']] ?? null;

                if (!$this->isPartNumber($no)) {
                    continue;
                }

                $name = trim((string) $name);
                if ($name === '') {
                    continue;
                }

                $cells = [];
                foreach ($header['decisions'] as $label => $col) {
                    $cells[$label] = $this->ref($col, $r);
                }

                $parts[] = [
                    'row' => $r,
                    'no' => is_float($no) ? (string) (int) $no : trim((string) $no),
                    'name' => $name,
                    'part_number' => $header['part_number_col']
                        ? trim((string) ($grid[$r][$header['part_number_col']] ?? ''))
                        : '',
                    'cells' => $cells,
                ];
            }
        }

        return [
            'profile' => $headers[0]['profile'],
            'headers' => array_map(
                static fn ($h) => ['row' => $h['row'], 'decisions' => $h['decisions']],
                $headers
            ),
            'parts' => $parts,
        ];
    }

    /**
     * Sel sheet jadi grid [baris][kolom] => nilai, supaya mudah discan.
     *
     * @param  array<string, mixed>  $sheet
     * @return array<int, array<int, mixed>>
     */
    private function grid(array $sheet): array
    {
        $grid = [];
        foreach ($sheet['cells'] ?? [] as $cell) {
            if (!isset($cell['v'], $cell['r'], $cell['c'])) {
                continue;
            }
            $grid[$cell['r']][$cell['c']] = $cell['v'];
        }

        ksort($grid);

        return $grid;
    }

    /**
     * Header bisa terpecah dua baris: "NO. | PARTS NAME | DECISION" di baris
     * pertama, lalu "U/A | U/R | R/N" di baris berikutnya. Karena itu label
     * keputusan dicari sampai dua baris ke bawah.
     *
     * @param  array<int, array<int, mixed>>  $grid
     * @return array<string, mixed>|null
     */
    private function detectHeaderRow(array $grid, int $row): ?array
    {
        $noCol = $nameCol = $partNumberCol = null;

        foreach ($grid[$row] ?? [] as $c => $value) {
            $text = $this->norm($value);
            if ($text === '') {
                continue;
            }

            if (preg_match('/^NO\.?$/', $text)) {
                $noCol = $c;
            }
            if (preg_match('/PARTS?\s*TO\s*REMOVE|PARTS?\s*NAME|^PARTS?$/', $text)) {
                $nameCol = $c;
            }
            if (preg_match('/^PART\s*NUMBER$|^P\/N$/', $text)) {
                $partNumberCol = $c;
            }
        }

        if ($nameCol === null) {
            return null;
        }
        $noCol ??= max(1, $nameCol - 1);

        $reuse = $salvage = $replace = $repair = null;
        $ua = $ur = $rn = null;
        $lastLabelRow = $row;

        for ($r = $row; $r <= $row + 2; $r++) {
            foreach ($grid[$r] ?? [] as $c => $value) {
                $text = $this->norm($value);
                if ($text === '') {
                    continue;
                }

                $hit = true;
                match (true) {
                    $text === 'REUSE' || $text === 'REUSED' => $reuse ??= $c,
                    str_starts_with($text, 'SALV') => $salvage ??= $c,
                    $text === 'REPLACE' || $text === 'REPLACE NEW' => $replace ??= $c,
                    $text === 'REPAIR' => $repair ??= $c,
                    $text === 'U/A' || $text === 'UA' => $ua ??= $c,
                    $text === 'U/R' || $text === 'UR' => $ur ??= $c,
                    $text === 'R/N' || $text === 'RN' => $rn ??= $c,
                    default => $hit = false,
                };

                if ($hit) {
                    $lastLabelRow = max($lastLabelRow, $r);
                }
            }
        }

        $base = [
            'row' => $row,
            'data_start' => $lastLabelRow + 1,
            'no_col' => $noCol,
            'name_col' => $nameCol,
            'part_number_col' => $partNumberCol,
        ];

        // Profil Powertrain: U/A | U/R | R/N
        if ($ur !== null && $rn !== null && $ur !== $rn) {
            return $base + [
                'profile' => 'inspection',
                'decisions' => array_filter(
                    ['U/A' => $ua, 'U/R' => $ur, 'R/N' => $rn],
                    static fn ($v) => $v !== null
                ),
            ];
        }

        // Profil Engine: REUSE | SALVAGE | REPLACE.
        // Sebagian template menulis kolom ketiga "REPAIR" — diterima apa adanya
        // supaya file Excel tidak perlu diseragamkan manual.
        if ($salvage !== null) {
            $replace ??= ($repair !== null && $repair > $salvage) ? $repair : null;
            $reuse ??= ($repair !== null && $repair < $salvage) ? $repair : null;

            $decisions = array_filter(
                ['REUSE' => $reuse, 'SALVAGE' => $salvage, 'REPLACE' => $replace],
                static fn ($v) => $v !== null
            );

            if (count($decisions) >= 2) {
                return $base + ['profile' => 'disassembly', 'decisions' => $decisions];
            }
        }

        return null;
    }

    private function norm(mixed $value): string
    {
        return strtoupper(trim(preg_replace('/\s+/', ' ', (string) $value) ?? ''));
    }

    private function isPartNumber(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        $s = trim((string) $value);
        if ($s === '' || in_array(strtoupper($s), ['NO', 'NO.'], true)) {
            return false;
        }

        return (bool) preg_match('/^\d+(\.0+)?$/', $s);
    }

    /** Kolom+baris (1-based) menjadi referensi sel Excel, mis. 20,17 -> T17. */
    public function ref(int $col, int $row): string
    {
        $letters = '';
        while ($col > 0) {
            $rem = ($col - 1) % 26;
            $letters = chr(65 + $rem) . $letters;
            $col = (int) (($col - $rem - 1) / 26);
        }

        return $letters . $row;
    }
}
