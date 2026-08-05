<?php

/**
 * Render PDF FR contoh ke file supaya layout-nya bisa diperiksa mata
 * tanpa perlu login ke aplikasi. Dipakai saat menyetel ulang tata letak
 * agar mendekati form asli PLO/09/F-021.
 *
 * Jalankan: php tools/preview_fr_pdf.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$component = new App\Models\Component([
    'serial_number' => 'DT090-0146B',
    'egi' => 'HD785-7',
    'model_type' => 'HD785-7',
    'major_category' => 'Powertrain',
    'component_model' => 'TORQUE FLOW',
    'unit_code' => 'DT090-0146B',
    'site_district' => 'ADMO',
    'mol_wo_number' => '2700046897',
]);
$component->comp_id = 1;

$fr = new App\Models\FabricationRequest([
    'fr_number' => 'FR/SIS/RC/0055/I/2026/INT',
    'part_number' => '561-13-71020',
    'part_name' => 'SHAFT',
    'qty' => 1,
    'brand' => 'KMT',
    'work_type' => 'repair',
    // Form asli boleh mencentang lebih dari satu jenis pekerjaan
    'work_types' => ['repair', 'fabrikasi'],
    'instruction' => 'POLESHING AREA BEARING SEAT',
    'sent_to' => 'LOKAL',
    'signatures' => [
        'approved_by' => ['name' => 'JATMIKO', 'date' => '2026-01-19'],
        'checked_by' => ['name' => 'ARY S', 'date' => '2026-01-19'],
        'ordered_by' => ['name' => 'PRAJA', 'date' => '2026-01-19'],
    ],
    'ro_number' => '2700046897',
    'location_site' => 'ADMO',
    'work_order_for' => 'FLATBED',
    'request_date' => '2026-01-19',
    'estimation_date' => '2026-02-04',
    'unit_price' => 1500000,
    'labour_cost' => 500000,
    'status' => 'draft',
]);
$fr->comp_id = 1;

$pdf = Barryvdh\DomPDF\Facade\Pdf::loadView('fr.pdf', compact('component', 'fr'));
$pdf->setPaper('a4', 'landscape');

$out = __DIR__ . '/../fr-preview.pdf';
file_put_contents($out, $pdf->output());

echo "PDF ditulis ke: {$out}\n";
