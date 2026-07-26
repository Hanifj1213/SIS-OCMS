<?php

namespace App\Services;

use Illuminate\Support\HtmlString;

/**
 * Ubah satu sheet hasil impor jadi tabel HTML yang tampil seperti di Excel:
 * lebar kolom, tinggi baris, merge, border, warna, perataan, dan gambar.
 *
 * Sel keputusan (dari decision_map) dirender sebagai checkbox supaya mekanik
 * mengisi langsung di halaman ini, tanpa Google Sheets.
 */
class SpreadsheetHtmlRenderer
{
    /** Lebar kolom default Excel (satuan karakter). */
    private const DEFAULT_COL_WIDTH = 8.43;

    /** Tinggi baris default (point). */
    private const DEFAULT_ROW_HEIGHT = 15.0;

    /** 1 piksel = 9525 EMU. */
    private const EMU_PER_PX = 9525;

    private const BORDER_WIDTH = [
        'hair' => '1px', 'thin' => '1px', 'dotted' => '1px', 'dashed' => '1px',
        'double' => '3px', 'medium' => '2px', 'mediumDashed' => '2px',
        'thick' => '3px', 'slantDashDot' => '2px',
    ];

    private const BORDER_STYLE = [
        'hair' => 'solid', 'thin' => 'solid', 'medium' => 'solid', 'thick' => 'solid',
        'dotted' => 'dotted', 'dashed' => 'dashed', 'mediumDashed' => 'dashed',
        'double' => 'double', 'slantDashDot' => 'dashed',
    ];

    /**
     * @param  array<string, mixed>  $sheet       satu entri dari layout['sheets']
     * @param  array<int, array<string, mixed>>  $styles  layout['styles']
     * @param  array<string, string>  $answers    ref sel -> nilai tersimpan
     * @param  array<string, mixed>|null  $decisions  decision_map untuk sheet ini
     */
    public function render(
        array $sheet,
        array $styles,
        array $answers = [],
        ?array $decisions = null,
        bool $editable = false,
    ): HtmlString {
        $maxRow = (int) ($sheet['max_row'] ?? 0);
        $maxCol = (int) ($sheet['max_col'] ?? 0);

        if ($maxRow === 0 || $maxCol === 0) {
            return new HtmlString('<p class="xl-empty">Sheet kosong.</p>');
        }

        $colPx = $this->columnPixels($sheet['cols'] ?? [], $maxCol);
        $rowPx = $this->rowPixels($sheet['rows'] ?? [], $maxRow);
        [$spans, $covered] = $this->mergeIndex($sheet['merges'] ?? []);
        $inputCells = $this->inputCells($decisions);

        $html = '<div class="xl-wrap"><div class="xl-canvas">';
        $html .= '<table class="xl-sheet"><colgroup>';
        for ($c = 1; $c <= $maxCol; $c++) {
            $html .= '<col style="width:' . $colPx[$c] . 'px">';
        }
        $html .= '</colgroup><tbody>';

        for ($r = 1; $r <= $maxRow; $r++) {
            $html .= '<tr style="height:' . $rowPx[$r] . 'px">';

            for ($c = 1; $c <= $maxCol; $c++) {
                $ref = $this->ref($c, $r);

                if (isset($covered[$ref])) {
                    continue;   // ditelan merge di kiri/atasnya
                }

                $cell = $sheet['cells'][$ref] ?? null;
                $style = $this->cellStyle($cell, $styles);
                $attr = '';

                if (isset($spans[$ref])) {
                    if ($spans[$ref]['rowspan'] > 1) {
                        $attr .= ' rowspan="' . $spans[$ref]['rowspan'] . '"';
                    }
                    if ($spans[$ref]['colspan'] > 1) {
                        $attr .= ' colspan="' . $spans[$ref]['colspan'] . '"';
                    }
                }

                if ($style !== '') {
                    $attr .= ' style="' . $style . '"';
                }

                $content = isset($inputCells[$ref])
                    ? $this->checkbox($ref, $inputCells[$ref], $answers[$ref] ?? null, $editable)
                    : $this->text($cell['v'] ?? null);

                $html .= '<td' . $attr . '>' . $content . '</td>';
            }

            $html .= '</tr>';
        }

        $html .= '</tbody></table>';
        $html .= $this->images($sheet['images'] ?? [], $colPx, $rowPx);
        $html .= '</div></div>';

        return new HtmlString($html);
    }

    /**
     * Lebar tiap kolom dalam piksel.
     *
     * @param  array<int, array<string, mixed>>  $cols
     * @return array<int, int>
     */
    private function columnPixels(array $cols, int $maxCol): array
    {
        $out = [];
        for ($c = 1; $c <= $maxCol; $c++) {
            $meta = $cols[$c] ?? null;
            if ($meta && !empty($meta['hidden'])) {
                $out[$c] = 0;
                continue;
            }
            $width = $meta['width'] ?? self::DEFAULT_COL_WIDTH;
            $out[$c] = max(0, (int) round($width * 7) + 5);
        }

        return $out;
    }

    /**
     * Tinggi tiap baris dalam piksel (point -> px).
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, int>
     */
    private function rowPixels(array $rows, int $maxRow): array
    {
        $out = [];
        for ($r = 1; $r <= $maxRow; $r++) {
            $meta = $rows[$r] ?? null;
            if ($meta && !empty($meta['hidden'])) {
                $out[$r] = 0;
                continue;
            }
            $height = $meta['height'] ?? self::DEFAULT_ROW_HEIGHT;
            $out[$r] = max(0, (int) round($height * 4 / 3));
        }

        return $out;
    }

    /**
     * @param  list<array<string, int>>  $merges
     * @return array{0: array<string, array{rowspan:int, colspan:int}>, 1: array<string, true>}
     */
    private function mergeIndex(array $merges): array
    {
        $spans = [];
        $covered = [];

        foreach ($merges as $m) {
            $anchor = $this->ref($m['c'], $m['r']);
            $spans[$anchor] = ['rowspan' => $m['rowspan'], 'colspan' => $m['colspan']];

            for ($r = $m['r']; $r < $m['r'] + $m['rowspan']; $r++) {
                for ($c = $m['c']; $c < $m['c'] + $m['colspan']; $c++) {
                    $ref = $this->ref($c, $r);
                    if ($ref !== $anchor) {
                        $covered[$ref] = true;
                    }
                }
            }
        }

        return [$spans, $covered];
    }

    /**
     * Sel yang harus jadi checkbox: ref -> label keputusan.
     *
     * @param  array<string, mixed>|null  $decisions
     * @return array<string, string>
     */
    private function inputCells(?array $decisions): array
    {
        if (!$decisions) {
            return [];
        }

        $out = [];
        foreach ($decisions['parts'] ?? [] as $part) {
            foreach ($part['cells'] ?? [] as $label => $ref) {
                $out[$ref] = $label;
            }
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>|null  $cell
     * @param  array<int, array<string, mixed>>  $styles
     */
    private function cellStyle(?array $cell, array $styles): string
    {
        $s = $styles[$cell['s'] ?? -1] ?? null;
        if (!$s) {
            return '';
        }

        $css = [];

        if ($font = $s['font'] ?? null) {
            if (!empty($font['bold'])) {
                $css[] = 'font-weight:700';
            }
            if (!empty($font['italic'])) {
                $css[] = 'font-style:italic';
            }
            if (!empty($font['underline'])) {
                $css[] = 'text-decoration:underline';
            }
            if (!empty($font['size'])) {
                $css[] = 'font-size:' . round($font['size'], 1) . 'pt';
            }
            if (!empty($font['color']) && $font['color'] !== '000000') {
                $css[] = 'color:#' . $font['color'];
            }
            if (!empty($font['name'])) {
                $css[] = "font-family:'" . str_replace("'", '', $font['name']) . "',sans-serif";
            }
        }

        if ($fill = $s['fill'] ?? null) {
            $css[] = 'background-color:#' . $fill;
        }

        foreach ($s['border'] ?? [] as $edge => $border) {
            $width = self::BORDER_WIDTH[$border['style']] ?? '1px';
            $style = self::BORDER_STYLE[$border['style']] ?? 'solid';
            $css[] = 'border-' . $edge . ':' . $width . ' ' . $style . ' #' . $border['color'];
        }

        if ($align = $s['align'] ?? null) {
            if (!empty($align['h'])) {
                $map = ['center' => 'center', 'right' => 'right', 'left' => 'left', 'justify' => 'justify'];
                if (isset($map[$align['h']])) {
                    $css[] = 'text-align:' . $map[$align['h']];
                }
            }
            if (!empty($align['v'])) {
                $map = ['top' => 'top', 'center' => 'middle', 'bottom' => 'bottom'];
                if (isset($map[$align['v']])) {
                    $css[] = 'vertical-align:' . $map[$align['v']];
                }
            }
            if (!empty($align['wrap'])) {
                $css[] = 'white-space:normal';
            }
            if (!empty($align['rot'])) {
                $rot = (int) $align['rot'];
                $deg = $rot === 255 ? 0 : ($rot > 90 ? 90 - $rot : -$rot);
                if ($deg !== 0) {
                    $css[] = 'writing-mode:vertical-rl';
                }
            }
        }

        return implode(';', $css);
    }

    private function text(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'TRUE' : 'FALSE';
        }

        if (is_float($value)) {
            // Excel menyimpan semua angka sebagai float; tampilkan bilangan
            // bulat tanpa ".0" supaya nomor urut tidak jadi "1.0".
            $value = (abs($value - round($value)) < 1e-9)
                ? (string) (int) round($value)
                : rtrim(rtrim(number_format($value, 4, '.', ''), '0'), '.');
        }

        return nl2br(e((string) $value));
    }

    private function checkbox(string $ref, string $label, ?string $value, bool $editable): string
    {
        $checked = $value === '1' ? ' checked' : '';
        $disabled = $editable ? '' : ' disabled';

        return '<input type="checkbox" class="xl-decision" data-ref="' . e($ref) . '"'
            . ' data-label="' . e($label) . '" title="' . e($label) . '"' . $checked . $disabled . '>';
    }

    /**
     * Gambar ditempel absolut di atas tabel, sesuai jangkar sel di Excel.
     *
     * @param  list<array<string, mixed>>  $images
     * @param  array<int, int>  $colPx
     * @param  array<int, int>  $rowPx
     */
    private function images(array $images, array $colPx, array $rowPx): string
    {
        if ($images === []) {
            return '';
        }

        $colLeft = $this->cumulative($colPx);
        $rowTop = $this->cumulative($rowPx);

        $html = '';
        foreach ($images as $img) {
            $from = $img['from'] ?? null;
            if (!$from) {
                continue;
            }

            $left = ($colLeft[$from['c']] ?? 0) + (int) round(($from['cOff'] ?? 0) / self::EMU_PER_PX);
            $top = ($rowTop[$from['r']] ?? 0) + (int) round(($from['rOff'] ?? 0) / self::EMU_PER_PX);

            if (isset($img['to'])) {
                $right = ($colLeft[$img['to']['c']] ?? $left) + (int) round(($img['to']['cOff'] ?? 0) / self::EMU_PER_PX);
                $bottom = ($rowTop[$img['to']['r']] ?? $top) + (int) round(($img['to']['rOff'] ?? 0) / self::EMU_PER_PX);
                $w = max(1, $right - $left);
                $h = max(1, $bottom - $top);
            } else {
                $w = $img['size']['w'] ?? 100;
                $h = $img['size']['h'] ?? 100;
            }

            $pos = "left:{$left}px;top:{$top}px;width:{$w}px;height:{$h}px";

            if (empty($img['url'])) {
                // EMF/WMF: browser tidak bisa menampilkannya.
                $html .= '<div class="xl-img-missing" style="' . $pos . '" title="Gambar vektor (EMF/WMF) belum dikonversi">'
                    . 'gambar vektor</div>';
                continue;
            }

            $html .= '<img class="xl-img" src="' . e($img['url']) . '" style="' . $pos . '" alt="" loading="lazy">';
        }

        return $html;
    }

    /**
     * Posisi kumulatif (kiri/atas) tiap kolom atau baris.
     *
     * @param  array<int, int>  $sizes
     * @return array<int, int>
     */
    private function cumulative(array $sizes): array
    {
        $out = [1 => 0];
        $acc = 0;
        $max = $sizes === [] ? 0 : max(array_keys($sizes));

        for ($i = 1; $i <= $max + 1; $i++) {
            $out[$i] = $acc;
            $acc += $sizes[$i] ?? 0;
        }

        return $out;
    }

    private function ref(int $col, int $row): string
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
