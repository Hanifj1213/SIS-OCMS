<?php

namespace App\Services;

use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

/**
 * Membaca layout .xlsx (nilai + gaya + merge + gambar) tanpa library luar.
 *
 * Dipakai untuk menampilkan checksheet secara lokal, 1:1 seperti di Excel,
 * tanpa perlu Google Sheets. HANYA MEMBACA — file tidak pernah ditulis ulang,
 * jadi tidak ada risiko gambar EMF/WMF hilang seperti waktu memakai openpyxl.
 */
class XlsxLayoutReader
{
    /** Sidecar HD Photo milik Office; tidak bisa ditampilkan browser. */
    private const SKIP_MEDIA_EXT = ['wdp', 'emz', 'wmz'];

    /** Format vektor yang perlu dikonversi dulu sebelum tampil di browser. */
    public const VECTOR_EXT = ['emf', 'wmf'];

    /** Palet warna indexed bawaan Excel (indeks 0-65). */
    private const INDEXED_COLORS = [
        0 => '000000', 1 => 'FFFFFF', 2 => 'FF0000', 3 => '00FF00', 4 => '0000FF',
        5 => 'FFFF00', 6 => 'FF00FF', 7 => '00FFFF', 8 => '000000', 9 => 'FFFFFF',
        10 => 'FF0000', 11 => '00FF00', 12 => '0000FF', 13 => 'FFFF00', 14 => 'FF00FF',
        15 => '00FFFF', 16 => '800000', 17 => '008000', 18 => '000080', 19 => '808000',
        20 => '800080', 21 => '008080', 22 => 'C0C0C0', 23 => '808080', 24 => '9999FF',
        25 => '993366', 26 => 'FFFFCC', 27 => 'CCFFFF', 28 => '660066', 29 => 'FF8080',
        30 => '0066CC', 31 => 'CCCCFF', 32 => '000080', 33 => 'FF00FF', 34 => 'FFFF00',
        35 => '00FFFF', 36 => '800080', 37 => '800000', 38 => '008080', 39 => '0000FF',
        40 => '00CCFF', 41 => 'CCFFFF', 42 => 'CCFFCC', 43 => 'FFFF99', 44 => '99CCFF',
        45 => 'FF99CC', 46 => 'CC99FF', 47 => 'FFCC99', 48 => '3366FF', 49 => '33CCCC',
        50 => '99CC00', 51 => 'FFCC00', 52 => 'FF9900', 53 => 'FF6600', 54 => '666699',
        55 => '969696', 56 => '003366', 57 => '339966', 58 => '003300', 59 => '333300',
        60 => '993300', 61 => '993366', 62 => '333399', 63 => '333333',
        64 => '000000', 65 => 'FFFFFF',
    ];

    private ZipArchive $zip;

    /** @var list<string> */
    private array $sharedStrings = [];

    /** @var array<int, array<string, mixed>> */
    private array $cellStyles = [];

    /** @var list<string> */
    private array $themeColors = [];

    /**
     * @param  list<string>|null  $sheetKeywords  hanya baca tab yang namanya memuat salah satu kata ini
     * @return array{source: string, sheets: list<array<string, mixed>>, styles: array<int, array<string, mixed>>}
     */
    public function read(string $path, ?array $sheetKeywords = null): array
    {
        if (!is_file($path)) {
            throw new RuntimeException("File tidak ditemukan: {$path}");
        }

        $this->zip = new ZipArchive();
        if ($this->zip->open($path) !== true) {
            throw new RuntimeException("Gagal membuka xlsx: {$path}");
        }

        try {
            $this->themeColors = $this->readTheme();
            $this->sharedStrings = $this->readSharedStrings();
            $this->cellStyles = $this->readStyles();

            $sheets = [];
            foreach ($this->readSheetIndex() as $entry) {
                if ($sheetKeywords !== null && !$this->nameMatches($entry['name'], $sheetKeywords)) {
                    continue;
                }

                $sheets[] = $this->readSheet($entry['name'], $entry['target']);
            }

            return [
                'source' => basename($path),
                'sheets' => $sheets,
                'styles' => $this->cellStyles,
            ];
        } finally {
            $this->zip->close();
        }
    }

    /** @param list<string> $keywords */
    private function nameMatches(string $name, array $keywords): bool
    {
        $low = mb_strtolower($name);
        foreach ($keywords as $kw) {
            if ($kw !== '' && str_contains($low, mb_strtolower($kw))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Buang seluruh namespace supaya akses SimpleXML sederhana.
     *
     * Tanpa ini, elemen hasil children($ns) tidak bisa dibaca atributnya
     * dengan $el['name'] — sumber bug yang sulit terlihat. Atribut berprefiks
     * seperti r:id diubah jadi r_id agar tetap terbaca.
     */
    private function xml(string $entry): ?SimpleXMLElement
    {
        $raw = $this->zip->getFromName($entry);
        if ($raw === false || $raw === '') {
            return null;
        }

        $raw = preg_replace('/\sxmlns(:[A-Za-z0-9_.-]+)?\s*=\s*"[^"]*"/', '', $raw) ?? $raw;
        $raw = preg_replace('/\s([A-Za-z0-9_.-]+):([A-Za-z0-9_.-]+)\s*=/', ' $1_$2=', $raw) ?? $raw;
        $raw = preg_replace('#<(/?)[A-Za-z0-9_.-]+:#', '<$1', $raw) ?? $raw;

        $prev = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($raw);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        return $xml ?: null;
    }

    /**
     * Daftar sheet beserta path xml-nya, urut sesuai tab di Excel.
     *
     * @return list<array{name: string, target: string}>
     */
    private function readSheetIndex(): array
    {
        $workbook = $this->xml('xl/workbook.xml');
        if (!$workbook) {
            return [];
        }

        $rels = [];
        $relXml = $this->xml('xl/_rels/workbook.xml.rels');
        if ($relXml) {
            foreach ($relXml->Relationship ?? [] as $rel) {
                $rels[(string) $rel['Id']] = (string) $rel['Target'];
            }
        }

        $out = [];
        foreach ($workbook->sheets->sheet ?? [] as $sheet) {
            $target = $rels[(string) $sheet['r_id']] ?? null;
            if (!$target) {
                continue;
            }

            $out[] = [
                'name' => (string) $sheet['name'],
                'target' => $this->resolvePath('xl', $target),
            ];
        }

        return $out;
    }

    /** @return list<string> */
    private function readSharedStrings(): array
    {
        $xml = $this->xml('xl/sharedStrings.xml');
        if (!$xml) {
            return [];
        }

        $out = [];
        foreach ($xml->si ?? [] as $si) {
            $out[] = $this->siText($si);
        }

        return $out;
    }

    private function siText(SimpleXMLElement $si): string
    {
        if (isset($si->t)) {
            return (string) $si->t;
        }

        // Rich text: gabungkan seluruh run.
        $text = '';
        foreach ($si->r ?? [] as $run) {
            $text .= (string) $run->t;
        }

        return $text;
    }

    /** @return list<string> */
    private function readTheme(): array
    {
        $xml = $this->xml('xl/theme/theme1.xml');
        $scheme = $xml->themeElements->clrScheme ?? null;
        if (!$scheme) {
            return [];
        }

        $out = [];
        foreach ($scheme->children() as $node) {
            if (isset($node->srgbClr)) {
                $out[] = strtoupper((string) $node->srgbClr['val']);
            } elseif (isset($node->sysClr)) {
                $out[] = strtoupper((string) ($node->sysClr['lastClr'] ?? '000000'));
            } else {
                $out[] = '000000';
            }
        }

        // Excel menukar dua entri pertama: file menulis dk1,lt1 tapi indeks
        // 0 = lt1 (background) dan 1 = dk1 (teks).
        if (count($out) >= 2) {
            [$out[0], $out[1]] = [$out[1], $out[0]];
        }

        return $out;
    }

    /**
     * Gaya per indeks cellXfs (dipakai atribut s= pada tiap sel).
     *
     * @return array<int, array<string, mixed>>
     */
    private function readStyles(): array
    {
        $xml = $this->xml('xl/styles.xml');
        if (!$xml) {
            return [];
        }

        $fonts = [];
        foreach ($xml->fonts->font ?? [] as $font) {
            $fonts[] = array_filter([
                'bold' => isset($font->b) ?: null,
                'italic' => isset($font->i) ?: null,
                'underline' => isset($font->u) ?: null,
                'size' => isset($font->sz) ? (float) $font->sz['val'] : null,
                'color' => isset($font->color) ? $this->color($font->color) : null,
                'name' => isset($font->name) ? (string) $font->name['val'] : null,
            ], static fn ($v) => $v !== null && $v !== false);
        }

        $fills = [];
        foreach ($xml->fills->fill ?? [] as $fill) {
            $pattern = $fill->patternFill ?? null;
            $type = $pattern ? (string) $pattern['patternType'] : 'none';
            $fills[] = ($pattern && $type !== 'none' && isset($pattern->fgColor))
                ? $this->color($pattern->fgColor)
                : null;
        }

        $borders = [];
        foreach ($xml->borders->border ?? [] as $border) {
            $side = [];
            foreach (['left', 'right', 'top', 'bottom'] as $edge) {
                $node = $border->{$edge} ?? null;
                $style = $node ? (string) $node['style'] : '';
                if ($style === '' || $style === 'none') {
                    continue;
                }
                $side[$edge] = [
                    'style' => $style,
                    'color' => (isset($node->color) ? $this->color($node->color) : null) ?? '000000',
                ];
            }
            $borders[] = $side;
        }

        $out = [];
        $index = 0;
        foreach ($xml->cellXfs->xf ?? [] as $xf) {
            $style = array_filter([
                'font' => $fonts[(int) ($xf['fontId'] ?? 0)] ?? null,
                'fill' => $fills[(int) ($xf['fillId'] ?? 0)] ?? null,
                'border' => $borders[(int) ($xf['borderId'] ?? 0)] ?? null,
            ], static fn ($v) => !empty($v));

            if (isset($xf->alignment)) {
                $a = $xf->alignment;
                $align = array_filter([
                    'h' => ((string) $a['horizontal']) ?: null,
                    'v' => ((string) $a['vertical']) ?: null,
                    'wrap' => (string) $a['wrapText'] === '1' ?: null,
                    'rot' => ((int) ($a['textRotation'] ?? 0)) ?: null,
                ], static fn ($v) => $v !== null && $v !== false);

                if ($align) {
                    $style['align'] = $align;
                }
            }

            $out[$index++] = $style;
        }

        return $out;
    }

    private function color(SimpleXMLElement $node): ?string
    {
        if (isset($node['rgb'])) {
            $rgb = strtoupper((string) $node['rgb']);

            return strlen($rgb) === 8 ? substr($rgb, 2) : $rgb;   // ARGB -> RGB
        }

        if (isset($node['indexed'])) {
            return self::INDEXED_COLORS[(int) $node['indexed']] ?? null;
        }

        if (isset($node['theme'])) {
            $base = $this->themeColors[(int) $node['theme']] ?? null;
            if ($base === null) {
                return null;
            }

            $tint = isset($node['tint']) ? (float) $node['tint'] : 0.0;

            return $tint === 0.0 ? $base : $this->applyTint($base, $tint);
        }

        return null;
    }

    private function applyTint(string $hex, float $tint): string
    {
        $out = '';
        foreach ([0, 2, 4] as $offset) {
            $v = (float) hexdec(substr($hex, $offset, 2));
            $v = $tint < 0 ? $v * (1 + $tint) : $v * (1 - $tint) + (255 * $tint);
            $out .= str_pad(dechex((int) round(max(0, min(255, $v)))), 2, '0', STR_PAD_LEFT);
        }

        return strtoupper($out);
    }

    /**
     * @return array<string, mixed>
     */
    private function readSheet(string $name, string $target): array
    {
        $xml = $this->xml($target);
        if (!$xml) {
            return [
                'name' => $name, 'max_row' => 0, 'max_col' => 0,
                'cols' => [], 'rows' => [], 'cells' => [], 'merges' => [], 'images' => [],
            ];
        }

        $cols = [];
        foreach ($xml->cols->col ?? [] as $col) {
            $min = (int) $col['min'];
            $max = min((int) $col['max'], 256);
            for ($c = $min; $c <= $max; $c++) {
                $cols[$c] = array_filter([
                    'width' => isset($col['width']) ? round((float) $col['width'], 2) : null,
                    'hidden' => (string) $col['hidden'] === '1' ?: null,
                ], static fn ($v) => $v !== null && $v !== false);
            }
        }

        $cells = [];
        $rows = [];
        $maxRow = 0;
        $maxCol = 0;

        foreach ($xml->sheetData->row ?? [] as $row) {
            $r = (int) $row['r'];
            $maxRow = max($maxRow, $r);

            $rowMeta = array_filter([
                'height' => isset($row['ht']) ? round((float) $row['ht'], 2) : null,
                'hidden' => (string) $row['hidden'] === '1' ?: null,
            ], static fn ($v) => $v !== null && $v !== false);

            if ($rowMeta) {
                $rows[$r] = $rowMeta;
            }

            foreach ($row->c ?? [] as $c) {
                $ref = (string) $c['r'];
                [$colIdx, $rowIdx] = $this->refToCoords($ref);
                if ($colIdx === 0) {
                    continue;
                }

                $value = $this->cellValue($c);
                $styleId = isset($c['s']) ? (int) $c['s'] : null;

                if ($value === null && ($styleId === null || empty($this->cellStyles[$styleId]))) {
                    continue;
                }

                $maxCol = max($maxCol, $colIdx);

                $cells[$ref] = array_filter([
                    'r' => $rowIdx,
                    'c' => $colIdx,
                    'v' => $value,
                    's' => $styleId,
                ], static fn ($v) => $v !== null);
            }
        }

        $merges = [];
        foreach ($xml->mergeCells->mergeCell ?? [] as $merge) {
            $ref = (string) $merge['ref'];
            if (!str_contains($ref, ':')) {
                continue;
            }
            [$from, $to] = explode(':', $ref, 2);
            [$c1, $r1] = $this->refToCoords($from);
            [$c2, $r2] = $this->refToCoords($to);
            if ($c1 === 0 || $c2 === 0) {
                continue;
            }
            $merges[] = [
                'r' => $r1,
                'c' => $c1,
                'rowspan' => $r2 - $r1 + 1,
                'colspan' => $c2 - $c1 + 1,
            ];
        }

        return [
            'name' => $name,
            'max_row' => $maxRow,
            'max_col' => $maxCol,
            'cols' => $cols,
            'rows' => $rows,
            'cells' => $cells,
            'merges' => $merges,
            'images' => $this->readImages($target),
        ];
    }

    private function cellValue(SimpleXMLElement $c): string|float|bool|null
    {
        $type = (string) $c['t'];

        if ($type === 'inlineStr') {
            return isset($c->is) ? $this->siText($c->is) : null;
        }

        if (!isset($c->v)) {
            return null;
        }

        $raw = (string) $c->v;

        return match ($type) {
            's' => $this->sharedStrings[(int) $raw] ?? '',
            'b' => $raw === '1',
            'e' => null,
            'str' => $raw,
            default => is_numeric($raw) ? (float) $raw : $raw,
        };
    }

    /** @return array{0:int,1:int} [kolom, baris] — 1-based; [0,0] jika ref tidak valid */
    private function refToCoords(string $ref): array
    {
        if (!preg_match('/^\$?([A-Z]+)\$?(\d+)$/i', $ref, $m)) {
            return [0, 0];
        }

        $col = 0;
        foreach (str_split(strtoupper($m[1])) as $ch) {
            $col = $col * 26 + (ord($ch) - 64);
        }

        return [$col, (int) $m[2]];
    }

    /**
     * Gambar yang ditempel di sheet, lengkap dengan posisi jangkarnya.
     *
     * @return list<array<string, mixed>>
     */
    private function readImages(string $sheetTarget): array
    {
        $relXml = $this->xml(dirname($sheetTarget) . '/_rels/' . basename($sheetTarget) . '.rels');
        if (!$relXml) {
            return [];
        }

        $drawingTarget = null;
        foreach ($relXml->Relationship ?? [] as $rel) {
            if (str_contains((string) $rel['Type'], '/drawing')) {
                $drawingTarget = $this->resolvePath(dirname($sheetTarget), (string) $rel['Target']);
                break;
            }
        }

        if (!$drawingTarget) {
            return [];
        }

        $drawing = $this->xml($drawingTarget);
        if (!$drawing) {
            return [];
        }

        $mediaRels = [];
        $drawRelXml = $this->xml(dirname($drawingTarget) . '/_rels/' . basename($drawingTarget) . '.rels');
        if ($drawRelXml) {
            foreach ($drawRelXml->Relationship ?? [] as $rel) {
                $mediaRels[(string) $rel['Id']] = $this->resolvePath(dirname($drawingTarget), (string) $rel['Target']);
            }
        }

        $images = [];

        foreach (['twoCellAnchor', 'oneCellAnchor'] as $anchorType) {
            foreach ($drawing->{$anchorType} ?? [] as $anchor) {
                if (!isset($anchor->pic->blipFill->blip, $anchor->from)) {
                    continue;
                }

                $media = $mediaRels[(string) $anchor->pic->blipFill->blip['r_embed']] ?? null;
                if (!$media) {
                    continue;
                }

                $ext = strtolower(pathinfo($media, PATHINFO_EXTENSION));
                if (in_array($ext, self::SKIP_MEDIA_EXT, true)) {
                    continue;
                }

                $from = $anchor->from;
                $entry = [
                    'media' => $media,
                    'ext' => $ext,
                    'vector' => in_array($ext, self::VECTOR_EXT, true),
                    'from' => [
                        'c' => (int) $from->col + 1,
                        'r' => (int) $from->row + 1,
                        'cOff' => (int) $from->colOff,
                        'rOff' => (int) $from->rowOff,
                    ],
                ];

                if ($anchorType === 'twoCellAnchor' && isset($anchor->to)) {
                    $entry['to'] = [
                        'c' => (int) $anchor->to->col + 1,
                        'r' => (int) $anchor->to->row + 1,
                        'cOff' => (int) $anchor->to->colOff,
                        'rOff' => (int) $anchor->to->rowOff,
                    ];
                } elseif (isset($anchor->ext)) {
                    // EMU -> piksel (1 px = 9525 EMU)
                    $entry['size'] = [
                        'w' => (int) round(((int) $anchor->ext['cx']) / 9525),
                        'h' => (int) round(((int) $anchor->ext['cy']) / 9525),
                    ];
                }

                $images[] = $entry;
            }
        }

        return $images;
    }

    private function resolvePath(string $base, string $target): string
    {
        if (str_starts_with($target, '/')) {
            return ltrim($target, '/');
        }

        $parts = [];
        foreach (explode('/', $base . '/' . $target) as $segment) {
            if ($segment === '..') {
                array_pop($parts);
            } elseif ($segment !== '.' && $segment !== '') {
                $parts[] = $segment;
            }
        }

        return implode('/', $parts);
    }

    /**
     * Ambil isi biner satu entri media (dipakai importer untuk menyimpan gambar).
     */
    public function extractMedia(string $xlsxPath, string $mediaEntry): ?string
    {
        $zip = new ZipArchive();
        if ($zip->open($xlsxPath) !== true) {
            return null;
        }

        $data = $zip->getFromName($mediaEntry);
        $zip->close();

        return $data === false ? null : $data;
    }
}
