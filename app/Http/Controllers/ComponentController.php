<?php

namespace App\Http\Controllers;

use App\Models\Component;
use App\Models\PartRequest;
use Illuminate\Http\Request;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

class ComponentController extends Controller
{
    /**
     * Nama deskriptif untuk setiap tahapan overhaul.
     */
    public const STAGE_NAMES = [
        1 => 'Receiving (Penerimaan)',
        2 => 'Disassembly (Pembongkaran)',
        3 => 'Measuring & Inspection (Pengukuran)',
        4 => 'Repair / Machining (Perbaikan)',
        5 => 'Assembly (Perakitan)',
        6 => 'Test Bench (Uji Fungsi)',
        7 => 'Painting & Finishing',
        8 => 'Delivery (Ready for Use)',
    ];

    /**
     * Daftar semua komponen.
     */
    public function index()
    {
        $components = Component::latest()->get();
        return view('overhauls.index', compact('components'));
    }

    /**
     * Form daftarkan komponen baru.
     */
    public function create()
    {
        return view('overhauls.create');
    }

    /**
     * Simpan komponen baru + generate QR Code + buat log awal.
     */
    public function store(Request $request)
    {
        $request->validate([
            'serial_number' => 'required|string|max:100|unique:components,serial_number',
            'model_type'    => 'required|string|max:255',
        ]);

        $component = Component::create([
            'serial_number' => strtoupper(trim($request->serial_number)),
            'model_type'    => trim($request->model_type),
            'current_stage' => 1,
            'status'        => 'On Progress',
        ]);

        // Generate QR Code
        $qrCode = new QrCode(route('components.show', $component->comp_id));
        $writer = new PngWriter();
        $result = $writer->write($qrCode);

        $dir = public_path('qrcodes');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $path = 'qrcodes/' . $component->comp_id . '.png';
        $result->saveToFile(public_path($path));

        $component->update(['qr_code_path' => $path]);

        // Create initial log (Stage 1: Receiving)
        $component->overhaulLogs()->create([
            'stage_number' => 1,
            'mechanic_id'  => auth()->id(),
            'start_time'   => now(),
            'notes'        => 'Komponen diterima di PRC (Receiving)',
        ]);

        return redirect()->route('components.show', $component->comp_id)
            ->with('success', 'Komponen "' . $component->serial_number . '" berhasil didaftarkan dan QR Code telah di-generate.');
    }

    /**
     * Tampilkan detail komponen beserta riwayat log, inspeksi, dan part request.
     */
    public function show(Component $component)
    {
        // Eager load semua relasi untuk menghindari N+1 query
        $component->load(['overhaulLogs.mechanic', 'inspectionDetails', 'partRequests']);

        $stageNames = self::STAGE_NAMES;

        return view('overhauls.show', ['comp' => $component, 'stageNames' => $stageNames]);
    }

    /**
     * Proses perpindahan tahapan + Quality Gate logic.
     */
    public function updateStage(Request $request, Component $component)
    {
        $currentStage = $component->current_stage;

        // Cegah stage melebihi 8
        if ($currentStage >= 8) {
            return back()->withErrors(['stage' => 'Komponen sudah mencapai tahap akhir (Delivery).']);
        }

        $nextStage = $currentStage + 1;

        // === TAHAP 3: Measuring & Inspection ===
        if ($currentStage == 3) {
            $request->validate([
                'parts'                 => 'required|array|min:1',
                'parts.*.name'          => 'required|string',
                'parts.*.actual_value'  => 'required|numeric|min:0',
                'parts.*.decision'      => 'required|in:Reused,Repair,Replace',
            ]);

            foreach ($request->parts as $partData) {
                $component->inspectionDetails()->create([
                    'part_name'    => $partData['name'],
                    'actual_value' => $partData['actual_value'],
                    'decision'     => $partData['decision'],
                ]);

                // Smart Inventory Trigger: otomatis buat Part Request jika Replace
                if ($partData['decision'] === 'Replace') {
                    $component->partRequests()->create([
                        'part_name' => $partData['name'],
                        'qty'       => 1,
                        'status'    => 'Pending',
                    ]);
                }
            }
        }

        // === TAHAP 6: Quality Gate (Test Bench) ===
        if ($currentStage == 6) {
            $request->validate([
                'oil_pressure' => 'required|numeric|min:0',
            ]);

            $pressure = (float) $request->oil_pressure;

            if ($pressure < 40 || $pressure > 50) {
                return back()->withErrors([
                    'oil_pressure' => 'GAGAL QC: Tekanan oli aktual (' . $pressure . ' psi) di luar toleransi spesifikasi (40 - 50 psi). Komponen TIDAK LOLOS uji fungsi, harap lakukan perbaikan ulang.',
                ])->withInput();
            }
        }

        // Tutup log tahapan saat ini
        $currentLog = $component->overhaulLogs()
            ->where('stage_number', $currentStage)
            ->latest('log_id')
            ->first();

        if ($currentLog) {
            $currentLog->update(['end_time' => now()]);
        }

        // Update status komponen
        $isDelivery = ($nextStage == 8);
        $component->update([
            'current_stage' => $nextStage,
            'status'        => $isDelivery ? 'Ready for Use' : 'On Progress',
        ]);

        // Buat log untuk tahapan selanjutnya
        $stageNote = self::STAGE_NAMES[$nextStage] ?? 'Tahap ' . $nextStage;
        $logData = [
            'stage_number' => $nextStage,
            'mechanic_id'  => auth()->id(),
            'start_time'   => now(),
            'notes'        => 'Memulai: ' . $stageNote,
        ];

        // Jika sudah tahap akhir (Delivery/RFU), langsung tutup lognya
        if ($isDelivery) {
            $logData['end_time'] = now();
            $logData['notes']    = 'Komponen selesai overhaul - Ready for Use (RFU)';
        }

        $component->overhaulLogs()->create($logData);

        return redirect()->route('components.show', $component->comp_id)
            ->with('success', 'Berhasil memproses ke ' . ($isDelivery ? 'status Ready for Use (RFU)!' : 'Tahap ' . $nextStage . ' (' . $stageNote . ')'));
    }

    /**
     * Cetak Berita Acara Serah Terima (BAST) dalam format PDF.
     */
    public function printPdf(Component $component)
    {
        $component->load(['overhaulLogs.mechanic', 'inspectionDetails', 'partRequests']);
        $stageNames = self::STAGE_NAMES;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('overhauls.pdf', compact('component', 'stageNames'));
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream('BAST-Overhaul-' . $component->serial_number . '.pdf');
    }
}
