<?php

namespace App\Http\Controllers;

use App\Models\ChecksheetTemplate;
use App\Models\Component;
use App\Models\ComponentChecksheet;
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
        1 => 'Receiving (Penerimaan DC)',
        2 => 'DIS Assembling (Pembongkaran, Pencucian & Pengukuran)',
        3 => 'Machining & Fabrication (Perbaikan)',
        4 => 'Assembly (Perakitan)',
        5 => 'Test Performance (Uji Fungsi)',
        6 => 'Painting (Pengecatan)',
        7 => 'RFU/Delivery (Siap Kirim)',
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
        $validCategories = [
            'Engine',
            'TC/Transmission',
            'Differential',
            'Final Drive',
            'PTO',
            'Control Valve',
            'Hydraulic Pump',
            'Travel Motor',
            'Swing Motor',
            'Swing Machinery',
            'Hydraulic Cylinder',
        ];

        $validStatusOvh = ['SCHEDULE', 'UNSCHEDULE'];

        $request->validate([
            // Data Unit
            'egi' => 'required|string|max:100',
            'unit_code' => 'required|string|max:100',
            'unit_serial_no' => 'nullable|string|max:100',
            'site_district' => 'required|string|max:100',
            // Data Komponen
            'major_category' => 'required|string|in:' . implode(',', $validCategories),
            'serial_number' => 'required|string|max:100|unique:components,serial_number',
            'pn_assy' => 'nullable|string|max:100',
            'status_ovh' => 'required|string|in:' . implode(',', $validStatusOvh),
            'core_category' => 'nullable|string|in:A,B,C',
            // Informasi Operasional
            'smr' => 'nullable|integer|min:0',
            'life_time' => 'nullable|integer|min:0',
            'date_defitted' => 'nullable|date',
            // Logistik
            'manifest' => 'nullable|string|max:255',
            'way_bill' => 'nullable|string|max:255',
        ]);

        $component = Component::create([
            // Data Unit
            'egi' => strtoupper(trim($request->egi)),
            'unit_code' => strtoupper(trim($request->unit_code)),
            'unit_serial_no' => $request->unit_serial_no ? strtoupper(trim($request->unit_serial_no)) : null,
            'site_district' => trim($request->site_district),
            // Data Komponen
            'major_category' => $request->major_category,
            'component_model' => $request->major_category, // Component Model = sama dengan kategori
            'serial_number' => strtoupper(trim($request->serial_number)),
            'model_type' => strtoupper(trim($request->egi)), // backward compat
            'pn_assy' => $request->pn_assy ? strtoupper(trim($request->pn_assy)) : null,
            'status_ovh' => $request->status_ovh,
            'core_category' => $request->core_category,
            // Informasi Operasional
            'smr' => $request->smr,
            'life_time' => $request->life_time,
            'date_defitted' => $request->date_defitted,
            // Logistik
            'manifest' => $request->manifest ? trim($request->manifest) : null,
            'way_bill' => $request->way_bill ? trim($request->way_bill) : null,
            // Default
            'current_stage' => 1,
            'status' => 'On Progress',
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
            'mechanic_id' => auth()->id(),
            'start_time' => now(),
            'notes' => 'Komponen diterima di PRC (Receiving)',
        ]);

        // Auto-generate checksheet from template for Stage 1
        $template = ChecksheetTemplate::where('major_category', $request->major_category)
            ->where('stage_number', 1)
            ->first();

        if ($template) {
            ComponentChecksheet::create([
                'comp_id' => $component->comp_id,
                'stage_number' => 1,
                'items' => $template->items,
                'answers' => [],
            ]);
        }

        return redirect()->route('components.show', $component->comp_id)
            ->with('success', 'Komponen "' . $component->serial_number . '" berhasil didaftarkan dan QR Code telah di-generate.');
    }

    /**
     * Tampilkan detail komponen beserta riwayat log, inspeksi, dan part request.
     */
    public function show(Component $component)
    {
        // Eager load semua relasi untuk menghindari N+1 query
        $component->load(['overhaulLogs.mechanic', 'inspectionDetails', 'partRequests', 'checksheets']);

        $stageNames = self::STAGE_NAMES;

        return view('overhauls.show', ['comp' => $component, 'stageNames' => $stageNames]);
    }

    /**
     * Proses perpindahan tahapan + Quality Gate logic.
     */
    public function updateStage(Request $request, Component $component)
    {
        // RBAC: hanya Mechanic, Supervisor, SuperAdmin yang boleh proses tahap
        if (!auth()->user()->hasAnyRole(['Mechanic', 'Supervisor', 'SuperAdmin'])) {
            return back()->withErrors(['stage' => 'Anda tidak memiliki izin untuk memproses tahapan. Hanya Mekanik, Supervisor, dan Admin yang diperbolehkan.']);
        }

        $currentStage = $component->current_stage;

        // Cegah stage melebihi 7
        if ($currentStage >= 7) {
            return back()->withErrors(['stage' => 'Komponen sudah mencapai tahap akhir (RFU/Delivery).']);
        }

        if ($component->is_waiting_approval) {
            return back()->withErrors(['stage' => 'Komponen ini sedang menunggu approval Management.']);
        }

        // Cek apakah checksheet tahap ini sudah diisi 100%
        $checksheet = $component->checksheets()->where('stage_number', $currentStage)->first();
        if ($checksheet && !$checksheet->is_complete) {
            return back()->withErrors(['stage' => 'Checksheet tahap ini belum selesai diisi. Progress saat ini: ' . $checksheet->progress . '%. Harap selesaikan checksheet sebelum melanjutkan.']);
        }

        $nextStage = $currentStage + 1;

        // === TAHAP 2: DIS Assembling (termasuk Measurement & Inspection) ===
        if ($currentStage == 2) {
            $request->validate([
                'parts' => 'required|array|min:1',
                'parts.*.name' => 'required|string',
                'parts.*.actual_value' => 'required|numeric|min:0',
                'parts.*.decision' => 'required|in:Reused,Repair,Replace',
            ]);

            foreach ($request->parts as $partData) {
                $component->inspectionDetails()->create([
                    'part_name' => $partData['name'],
                    'actual_value' => $partData['actual_value'],
                    'decision' => $partData['decision'],
                ]);

                // Smart Inventory Trigger: otomatis buat Part Request jika Replace
                if ($partData['decision'] === 'Replace') {
                    $component->partRequests()->create([
                        'part_name' => $partData['name'],
                        'qty' => 1,
                        'status' => 'Pending',
                    ]);
                }
            }
        }

        // === TAHAP 5: Quality Gate (Test Performance) ===
        if ($currentStage == 5) {
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

        // Tambahkan catatan mekanik ke log saat ini
        if ($request->filled('remarks')) {
            $currentLog = $component->overhaulLogs()
                ->where('stage_number', $currentStage)
                ->latest('log_id')
                ->first();

            if ($currentLog) {
                $currentLog->update([
                    'notes' => $currentLog->notes . "\n\nCatatan Mekanik: " . $request->remarks
                ]);
            }
        }

        // Cek apakah tahap ini memerlukan approval sebelum lanjut ke tahap berikutnya
        $requiresApproval = in_array($currentStage, [2, 3, 4, 5]);

        if ($requiresApproval) {
            // Ubah status menjadi menunggu approval
            $component->update([
                'is_waiting_approval' => true,
            ]);

            return redirect()->route('components.show', $component->comp_id)
                ->with('success', 'Progress tahap ' . $currentStage . ' selesai. Menunggu Approval Management untuk lanjut ke Tahap ' . $nextStage . '.');
        } else {
            // Auto-transition (tidak perlu approval)
            
            // Tutup log tahapan saat ini
            $currentLog = $component->overhaulLogs()
                ->where('stage_number', $currentStage)
                ->latest('log_id')
                ->first();

            if ($currentLog) {
                $currentLog->update(['end_time' => now()]);
            }

            // Update status komponen
            $isFinalCompleted = ($nextStage == 7);
            $component->update([
                'current_stage' => $nextStage,
                'is_waiting_approval' => false,
                'status' => $isFinalCompleted ? 'Ready for Use' : 'On Progress',
            ]);

            // Buat log untuk tahapan selanjutnya
            $stageNote = self::STAGE_NAMES[$nextStage] ?? 'Tahap ' . $nextStage;
            $logData = [
                'stage_number' => $nextStage,
                'mechanic_id' => auth()->id(),
                'start_time' => now(),
                'notes' => 'Memulai: ' . $stageNote,
            ];

            // Jika sudah tahap akhir (RFU/Delivery), langsung tutup lognya
            if ($isFinalCompleted) {
                $logData['end_time'] = now();
                $logData['notes'] = 'RFU/Delivery selesai - Komponen Ready for Use (RFU)';
            }

            $component->overhaulLogs()->create($logData);

            return redirect()->route('components.show', $component->comp_id)
                ->with('success', 'Tahap ' . $currentStage . ' selesai! Komponen langsung berlanjut ke ' . ($isFinalCompleted ? 'status Ready for Use (RFU)' : 'Tahap ' . $nextStage));
        }
    }

    /**
     * Setujui transisi tahap oleh Management
     */
    public function approveStage(Component $component)
    {
        if (!auth()->user()->hasRole('Management')) {
            return back()->withErrors(['approval' => 'Hanya role Management yang dapat memberikan approval.']);
        }

        if (!$component->is_waiting_approval) {
            return back()->withErrors(['approval' => 'Komponen ini tidak sedang menunggu approval.']);
        }

        $currentStage = $component->current_stage;
        $nextStage = $currentStage + 1;

        // Tutup log tahapan saat ini
        $currentLog = $component->overhaulLogs()
            ->where('stage_number', $currentStage)
            ->latest('log_id')
            ->first();

        if ($currentLog) {
            $currentLog->update(['end_time' => now()]);
        }

        // Update status komponen
        $isFinalCompleted = ($nextStage == 7);
        $component->update([
            'current_stage' => $nextStage,
            'is_waiting_approval' => false,
            'status' => $isFinalCompleted ? 'Ready for Use' : 'On Progress',
        ]);

        // Buat log untuk tahapan selanjutnya
        $stageNote = self::STAGE_NAMES[$nextStage] ?? 'Tahap ' . $nextStage;
        $logData = [
            'stage_number' => $nextStage,
            'mechanic_id' => auth()->id(), // Idealnya mencatat yang approve atau tetap mekanik sebelumnya, tapi kita pakai auth() untuk jejak Management yang trigger
            'start_time' => now(),
            'notes' => 'Memulai: ' . $stageNote . ' (Approved)',
        ];

        // Jika sudah tahap akhir (RFU/Delivery), langsung tutup lognya
        if ($isFinalCompleted) {
            $logData['end_time'] = now();
            $logData['notes'] = 'RFU/Delivery selesai - Komponen Ready for Use (RFU) (Approved)';
        }

        $component->overhaulLogs()->create($logData);

        return redirect()->route('components.show', $component->comp_id)
            ->with('success', 'Approval berhasil! Komponen berlanjut ke ' . ($isFinalCompleted ? 'status Ready for Use (RFU)' : 'Tahap ' . $nextStage));
    }

    /**
     * Tolak transisi tahap oleh Management
     */
    public function rejectStage(Component $component)
    {
        if (!auth()->user()->hasRole('Management')) {
            return back()->withErrors(['approval' => 'Hanya role Management yang dapat menolak approval.']);
        }

        if (!$component->is_waiting_approval) {
            return back()->withErrors(['approval' => 'Komponen ini tidak sedang menunggu approval.']);
        }

        $component->update([
            'is_waiting_approval' => false,
        ]);

        return redirect()->route('components.show', $component->comp_id)
            ->with('success', 'Approval ditolak. Komponen dikembalikan ke mekanik pada tahap ' . $component->current_stage . '.');
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
