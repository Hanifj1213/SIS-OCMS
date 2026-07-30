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

        $isCylinderHeadDisassembly = $this->isCylinderHeadDisassembly($sheet, $headers);
        $parts = [];
        $lastPartNo = null;
        $lastPartName = null;
        foreach ($headers as $i => $header) {
            // Jangan ikutkan baris header berikutnya — kalau ikut, checkbox menimpa
            // teks REUSE/SALVAGE/REPLACE di header ulang.
            $sectionEnd = isset($headers[$i + 1])
                ? ((int) $headers[$i + 1]['row'] - 1)
                : (int) ($sheet['max_row'] ?? 0);
            $sectionParts = [];
            $lastAddedRow = null;

            for ($r = $header['data_start']; $r <= $sectionEnd; $r++) {
                if ($isCylinderHeadDisassembly && $this->isCylinderHeadDecisionBoundary($grid, $r, $header)) {
                    break;
                }

                $no = $grid[$r][$header['no_col']] ?? null;
                $name = $grid[$r][$header['name_col']] ?? null;
                $name = trim((string) $name);
                $isNumberedPart = $this->isPartNumber($no);
                $nextName = trim((string) ($grid[$r + 1][$header['name_col']] ?? ''));
                $isContinuation = !$isNumberedPart
                    && $lastPartNo !== null
                    && ($this->isContinuedPartName($name) || $this->isContinuedPartName($nextName));

                if ($isNumberedPart) {
                    // Angka pada tabel measurement/footer bukan part jika kolom
                    // nama part kosong (contoh Timing Gear di bawah #33).
                    if ($name === '') {
                        continue;
                    }
                    $lastPartNo = is_float($no) ? (string) (int) $no : trim((string) $no);
                    $lastPartName = $name;
                } elseif (!$isContinuation) {
                    continue;
                }

                // Pola template: row 178 "Camshaft", row 179 "continued".
                // Jangan tambahkan row 179 lagi jika row 178 sudah dijadikan awal block.
                if ($this->isContinuedPartName($name) && $lastAddedRow === $r - 1) {
                    continue;
                }

                if ($name === '' && $lastPartName === null) {
                    continue;
                }

                $sectionParts[] = [
                    'row' => $r,
                    'no' => $lastPartNo,
                    // Continuation adalah part fisik yang sama; nama disamakan
                    // agar scan FR tidak membuat dua kandidat untuk satu part.
                    'name' => $isContinuation ? ($lastPartName ?: $name) : $name,
                    'part_number' => $header['part_number_col']
                        ? trim((string) ($grid[$r][$header['part_number_col']] ?? ''))
                        : '',
                    'continued' => $isContinuation,
                ];
                $lastAddedRow = $r;
            }

            for ($j = 0; $j < count($sectionParts); $j++) {
                $startRow = $sectionParts[$j]['row'];
                $hasNextPart = isset($sectionParts[$j + 1]);
                $blockEnd = $hasNextPart
                    ? $sectionParts[$j + 1]['row'] - 1
                    : $sectionEnd;

                // Potong di footer tanda tangan / section pengukuran.
                $blockEnd = $this->clampBlockEnd($grid, $startRow, $blockEnd);

                if ($isCylinderHeadDisassembly) {
                    $blockEnd = $this->clampCylinderHeadDecisionEnd($grid, $startRow, $blockEnd, $header);
                }

                // Part terakhir section: jangan taruh keputusan di baris kosong
                // page-break tepat di atas header ulang (kelihatan nempel REUSE).
                if (!$hasNextPart || $isCylinderHeadDisassembly) {
                    $blockEnd = $this->lastContentRowInBlock($grid, $startRow, $blockEnd, $header);
                }

                if ($blockEnd < $startRow) {
                    $blockEnd = $startRow;
                }

                // Kotak keputusan = area merge vertikal + checkbox.
                // Normalnya seluruh block part; kalau ada panduan posisi
                // (RH/LH/FRONT/CENTRE/REAR) kotak mulai tepat DI BAWAH panduan.
                $boxStart = $this->decisionBoxStart($grid, $startRow, $blockEnd, $header);

                $cells = [];
                foreach ($header['decisions'] as $label => $col) {
                    $cells[$label] = $this->ref($col, $boxStart);
                }

                $parts[] = $sectionParts[$j] + [
                    'decision_row' => $boxStart,
                    'box_start' => $boxStart,
                    'box_end' => $blockEnd,
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
     * CYL HEAD DISASSY mencampur tabel part dan tabel measurement pada kolom
     * NO/PART yang sama. Baris measurement harus dikeluarkan dari decision_map.
     *
     * @param  list<array<string, mixed>>  $headers
     */
    private function isCylinderHeadDisassembly(array $sheet, array $headers): bool
    {
        if (!str_contains($this->norm($sheet['name'] ?? ''), 'CYL HEAD')
            || ($headers[0]['profile'] ?? null) !== 'disassembly'
        ) {
            return false;
        }

        return str_contains($this->norm($sheet['name'] ?? ''), 'DISASS');
    }

    /**
     * Awal area measurement CYL HEAD. Sesudah penanda ini angka di kolom NO
     * adalah nomor model/item pengukuran, bukan nomor part disassembly.
     *
     * @param  array<int, array<int, mixed>>  $grid
     * @param  array<string, mixed>  $header
     */
    private function isCylinderHeadDecisionBoundary(array $grid, int $row, array $header): bool
    {
        $no = $this->norm($grid[$row][$header['no_col']] ?? '');

        return preg_match(
            '/^(?:NO\.?|ACTUAL|ENGINE MODEL|STANDARD OF\b|[A-Z]\.?\s*MEASURE\b|MEASURE\b|VALVE SPRINGS?|VALVE GUIDES?)$/',
            $no
        ) === 1
            || preg_match('/^(?:STANDARD OF|[A-Z]\.?\s*MEASURE|MEASURE)\b/', $no) === 1;
    }

    /**
     * @param  array<int, array<int, mixed>>  $grid
     * @param  array<string, mixed>  $header
     */
    private function clampCylinderHeadDecisionEnd(
        array $grid,
        int $startRow,
        int $blockEnd,
        array $header
    ): int {
        for ($r = $startRow + 1; $r <= $blockEnd; $r++) {
            if ($this->isCylinderHeadDecisionBoundary($grid, $r, $header)) {
                return max($startRow, $r - 1);
            }
        }

        return $blockEnd;
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
     * Baris konten terakhir di dalam block (abaikan spacer kosong sebelum header ulang).
     *
     * @param  array<int, array<int, mixed>>  $grid
     * @param  array<string, mixed>  $header
     */
    private function lastContentRowInBlock(array $grid, int $startRow, int $blockEnd, array $header): int
    {
        if ($blockEnd <= $startRow) {
            return $startRow;
        }

        $decisionCols = array_values($header['decisions'] ?? []);
        $decisionMin = $decisionCols !== [] ? min($decisionCols) : PHP_INT_MAX;
        $last = $startRow;

        for ($r = $startRow; $r <= $blockEnd; $r++) {
            if ($this->isBlockBoundaryRow($grid, $r)) {
                break;
            }

            $has = false;
            foreach ($grid[$r] ?? [] as $c => $v) {
                // Hanya area NO / part / kondisi — bukan kolom keputusan/sketch.
                if ((int) $c >= $decisionMin) {
                    continue;
                }
                if (trim((string) $v) !== '') {
                    $has = true;
                    break;
                }
            }

            if ($has) {
                $last = $r;
            }
        }

        return $last;
    }

    /**
     * Baris awal kotak keputusan (merge vertikal + checkbox).
     *
     * RH/LH/FRONT/CENTRE/REAR adalah panduan posisi inspeksi, bukan baris
     * keputusan — checkbox harus berada tepat di bawah grid panduan itu supaya
     * tidak menghalangi pengisian data.
     *
     * @param  array<int, array<int, mixed>>  $grid
     * @param  array<string, mixed>  $header
     */
    private function decisionBoxStart(array $grid, int $startRow, int $blockEnd, array $header): int
    {
        if ($blockEnd <= $startRow) {
            return $startRow;
        }

        $decisionCols = array_values($header['decisions'] ?? []);
        $decisionMin = $decisionCols !== [] ? min($decisionCols) : PHP_INT_MAX;
        $nameCol = (int) $header['name_col'];

        $lastGuideRow = null;
        for ($r = $startRow; $r <= $blockEnd; $r++) {
            foreach ($grid[$r] ?? [] as $c => $value) {
                $c = (int) $c;
                if ($c <= $nameCol || $c >= $decisionMin) {
                    continue;
                }
                if ($this->isPositionGuideLabel($value)) {
                    $lastGuideRow = $r;
                    break;
                }
            }
        }

        if ($lastGuideRow === null) {
            return $startRow;
        }

        return min($lastGuideRow + 1, $blockEnd);
    }

    private function isPositionGuideLabel(mixed $value): bool
    {
        return in_array($this->norm($value), [
            'RH', 'LH', 'R/H', 'L/H', 'FRONT', 'CENTRE', 'CENTER', 'REAR',
        ], true);
    }

    /**
     * Potong akhir block part sebelum footer tanda tangan / section berikutnya.
     *
     * @param  array<int, array<int, mixed>>  $grid
     */
    private function clampBlockEnd(array $grid, int $startRow, int $blockEnd): int
    {
        if ($blockEnd <= $startRow) {
            return $startRow;
        }

        for ($r = $startRow + 1; $r <= $blockEnd; $r++) {
            if ($this->isBlockBoundaryRow($grid, $r)) {
                return max($startRow, $r - 1);
            }
        }

        return $blockEnd;
    }

    /**
     * Baris yang menandai akhir area part (bukan isi kondisi part).
     *
     * @param  array<int, array<int, mixed>>  $grid
     */
    private function isBlockBoundaryRow(array $grid, int $row): bool
    {
        foreach ($grid[$row] ?? [] as $value) {
            $text = $this->norm($value);
            if ($text === '') {
                continue;
            }

            // Footer tanda tangan, section pengukuran gear, remarks — bukan isi part.
            if (preg_match(
                '/^(INSPECTION BY|APPROVAL BY|CHECKED BY|MECHANIC|PROD\.?\s*SUPERVISOR|REMARKS?:?|ATTACHED)\b/',
                $text
            )) {
                return true;
            }

            // D375/PC1250: "FRONT SIDE TIMING GEAR MEASUREMENT"
            // GD825A: "BACKLASH TIMING GEAR" / "END PLAY TIMING GEAR"
            if (preg_match(
                '/\b(TIMING\s*GEAR|BACKLASH\s*TIMING|END\s*PLAY\s*TIMING)\b/',
                $text
            )) {
                return true;
            }
        }

        return false;
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

    private function isContinuedPartName(string $name): bool
    {
        return $name !== '' && preg_match('/\bCONTINUED\b/i', $name) === 1;
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
