<?php

/**
 * Render satu layout jadi file HTML mandiri, untuk memeriksa kemiripan
 * tampilannya dengan Excel tanpa perlu menjalankan server.
 *
 *   php tools/render_layout_preview.php                       # semua contoh
 *   php tools/render_layout_preview.php 12                    # layout_id 12
 *   php tools/render_layout_preview.php 12 "INSPEKSI NO2"     # tab tertentu
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\SpreadsheetLayout;
use App\Services\SpreadsheetHtmlRenderer;

$outDir = __DIR__ . '/preview';
if (!is_dir($outDir)) {
    mkdir($outDir, 0775, true);
}

$renderer = app(SpreadsheetHtmlRenderer::class);

$layoutId = $argv[1] ?? null;
$sheetName = $argv[2] ?? null;

$layouts = $layoutId
    ? SpreadsheetLayout::where('layout_id', $layoutId)->get()
    : SpreadsheetLayout::query()
        ->whereIn('kind', ['inspection', 'disassembly'])
        ->get()
        ->groupBy('major_category')
        ->map(fn ($g) => $g->first())
        ->values();

if ($layouts->isEmpty()) {
    exit("Tidak ada layout. Jalankan: php artisan checksheet:import-layouts\n");
}

printf("%-42s %-22s %7s %7s %7s %9s\n", 'file', 'tab', 'baris', 'gambar', 'centang', 'ukuran');
echo str_repeat('-', 100), "\n";

foreach ($layouts as $layout) {
    foreach ($layout->sheets() as $sheet) {
        if ($sheetName !== null && $sheet['name'] !== $sheetName) {
            continue;
        }

        $decisions = $layout->decision_map[$sheet['name']] ?? null;
        $body = $renderer->render($sheet, $layout->styles(), [], $decisions, true);

        $title = sprintf(
            '%s %s — %s [%s]',
            $layout->major_category,
            $layout->egi_model ?: '',
            $layout->kind,
            $sheet['name']
        );

        $html = "<!DOCTYPE html><html lang=\"id\"><head><meta charset=\"utf-8\">"
            . "<title>{$title}</title><style>"
            . 'body{margin:0;background:#f1f3f5;font-family:Calibri,"Segoe UI",sans-serif}'
            . '.hdr{padding:10px 16px;background:#fff;border-bottom:1px solid #d0d7de;font-size:14px;font-weight:600}'
            . '.xl-wrap{overflow:auto;padding:18px}'
            . '.xl-canvas{position:relative;display:inline-block;background:#fff;box-shadow:0 1px 4px rgba(0,0,0,.12)}'
            . 'table.xl-sheet{border-collapse:collapse;table-layout:fixed;font-size:11pt;line-height:1.15}'
            . 'table.xl-sheet td{padding:0 2px;overflow:hidden;white-space:nowrap;vertical-align:bottom;word-break:break-word}'
            . '.xl-img{position:absolute;object-fit:fill;pointer-events:none}'
            . '.xl-img-missing{position:absolute;display:flex;align-items:center;justify-content:center;font-size:10px;'
            . 'color:#9a6700;background:#fff8c5;border:1px dashed #d4a72c;text-align:center}'
            . '.xl-decision{width:15px;height:15px;accent-color:#1f6feb}'
            . '</style></head><body>'
            . "<div class=\"hdr\">{$title}</div>"
            . $body
            . '</body></html>';

        // Gambar dirujuk lewat asset() -> arahkan ke file lokal supaya
        // pratinjau bisa dibuka langsung tanpa server.
        $html = str_replace(
            [asset('checksheet-media') . '/', 'http://localhost/checksheet-media/'],
            str_replace('\\', '/', public_path('checksheet-media')) . '/',
            $html
        );

        $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($title));
        $file = $outDir . '/' . trim($slug, '-') . '.html';
        file_put_contents($file, $html);

        printf(
            "%-42s %-22s %7d %7d %7d %8.0f K\n",
            mb_substr($layout->source_file, 0, 40),
            mb_substr($sheet['name'], 0, 20),
            $sheet['max_row'],
            count($sheet['images']),
            substr_count($html, 'xl-decision'),
            strlen($html) / 1024
        );
    }
}

echo "\nHasil: tools/preview/*.html — buka di browser untuk membandingkan dengan Excel.\n";
