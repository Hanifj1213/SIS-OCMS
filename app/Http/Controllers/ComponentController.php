<?php

namespace App\Http\Controllers;

use App\Models\ChecksheetTemplate;
use App\Models\Component;
use App\Models\ComponentChecksheet;
use App\Jobs\DuplicateChecksheetGsheets;
use App\Models\PartRequest;
use App\Services\ChecksheetGsheetService;
use App\Services\FabricationRequestService;
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
        5 => 'Test Performance & Painting (Uji Fungsi & Pengecatan)',
        6 => 'Delivery (Serah Terima)',
        7 => 'RFU (Ready for Use)',
    ];

    /**
     * Clone the EGI-specific template when a component enters a stage.
     * Existing answers are never overwritten, so the snapshot remains an
     * auditable record of the checklist used for that component.
     */
    private function ensureChecksheetForStage(Component $component, int $stage): void
    {
        if ($component->checksheets()->where('stage_number', $stage)->exists()) {
            return;
        }

        $egi = strtoupper(trim((string) $component->egi));
        $template = ChecksheetTemplate::query()
            ->where('major_category', $component->major_category)
            ->where('stage_number', $stage)
            ->whereRaw('UPPER(egi_model) = ?', [$egi])
            ->first();

        $template ??= ChecksheetTemplate::query()
            ->where('major_category', $component->major_category)
            ->where('stage_number', $stage)
            ->whereNull('egi_model')
            ->first();

        if (!$template) {
            return;
        }

        ComponentChecksheet::create([
            'comp_id' => $component->comp_id,
            'stage_number' => $stage,
            'items' => $template->items,
            'answers' => [],
        ]);
    }

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
            'Front Suspension',
            'Rear Suspension',
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
        // Try EGI-specific template first, then fallback to generic
        $egiNormalized = strtoupper(trim($request->egi));
        $template = ChecksheetTemplate::where('major_category', $request->major_category)
            ->where('stage_number', 1)
            ->where(function ($q) use ($egiNormalized) {
                $q->whereRaw('UPPER(egi_model) = ?', [$egiNormalized]);
            })
            ->first();

        if (!$template) {
            $template = ChecksheetTemplate::where('major_category', $request->major_category)
                ->where('stage_number', 1)
                ->whereNull('egi_model')
                ->first();
        }

        if ($template) {
            ComponentChecksheet::create([
                'comp_id' => $component->comp_id,
                'stage_number' => 1,
                'items' => $template->items,
                'answers' => [],
            ]);
        }

        // Duplikasi template Google Sheets dijalankan di LATAR BELAKANG.
        // Ada empat jenis template dan tiap panggilan Apps Script bisa 10–20
        // detik, sehingga totalnya melewati batas 30 detik PHP dan membuat
        // pendaftaran gagal padahal komponen sudah tersimpan.
        DuplicateChecksheetGsheets::dispatch($component->comp_id);

        return redirect()->route('components.show', $component->comp_id)
            ->with('success', 'Komponen "' . $component->serial_number . '" berhasil didaftarkan dan QR Code telah di-generate.');
    }

    /**
     * Tampilkan detail komponen beserta riwayat log, inspeksi, dan part request.
     */
    public function show(Request $request, Component $component)
    {
        // Eager load semua relasi untuk menghindari N+1 query
        $component->load(['overhaulLogs.mechanic', 'inspectionDetails', 'partRequests', 'checksheets', 'fabricationRequests']);

        $stageNames = self::STAGE_NAMES;
        $requestedReviewStage = $request->integer('review_stage');
        $reviewStage = null;

        // Review hanya boleh membuka tahap yang sudah pernah dicapai. Parameter
        // ini tidak pernah mengubah current_stage atau status proses komponen.
        if (
            $requestedReviewStage >= 1 && $requestedReviewStage <= 7
            && $requestedReviewStage < $component->current_stage
        ) {
            $reviewStage = $requestedReviewStage;
        }

        // Pastikan checksheet untuk stage ini sudah ter-generate dari template
        $this->ensureChecksheetForStage($component, $reviewStage ?? $component->current_stage);
        $component->load('checksheets'); // Reload jika baru digenerate

        // Retry duplikasi GSheet untuk komponen lama / yang gagal saat daftar.
        // Dikerjakan di latar belakang: memanggil Apps Script langsung di sini
        // membuat halaman detail menggantung sampai belasan detik.
        $gsheetService = app(ChecksheetGsheetService::class);
        if ($gsheetService->hasPendingDuplication($component)) {
            DuplicateChecksheetGsheets::dispatch($component->comp_id);
        }

        // Metrik waktu 3 dimensi (Calendar/Work/Man Hour) per tahap
        $stageTimeMetrics = app(\App\Services\StageTimeService::class)->metricsFor($component);

        return view('overhauls.show', [
            'comp' => $component,
            'stageNames' => $stageNames,
            'reviewStage' => $reviewStage,
            'assemblyTemplateAvailable' => (bool) $gsheetService->templateIdFor($component, 'assembly'),
            'testbenchTemplateAvailable' => (bool) $gsheetService->templateIdFor($component, 'testbench'),
            'stageTimeMetrics' => $stageTimeMetrics,
        ]);
    }

    /**
     * Proses perpindahan tahapan.
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
            return back()->withErrors(['stage' => 'Komponen sudah mencapai tahap akhir (RFU).']);
        }

        if ($component->is_waiting_approval) {
            return back()->withErrors(['stage' => 'Komponen ini sedang menunggu approval Management.']);
        }

        // Cek apakah checksheet tahap ini sudah diisi 100%.
        // Dikecualikan bila tahap ini memakai checksheet Google Sheets
        // (spreadsheet menggantikan checksheet internal, progressnya tidak
        // terlacak di database).
        // Stage 2 memakai spreadsheet bila komponen punya gsheet_url
        // (Engine mainline / Powertrain Control Valve dkk.).
        // Stage 4 (Assembly) & 5 (Test Bench) juga memakai spreadsheet bila
        // salinan GSheet-nya tersedia.
        $usesGsheetChecksheet = ($currentStage == 2
            && (
                (bool) $component->gsheet_url
                || (
                    $component->major_category === 'Engine'
                    && strtoupper(trim((string) $component->egi)) === 'PC2000-8'
                )
            ))
            || ($currentStage == 4 && (bool) $component->gsheet_assembly_url)
            || ($currentStage == 5 && (bool) $component->gsheet_testbench_url);

        $checksheet = $component->checksheets()->where('stage_number', $currentStage)->first();
        if (!$usesGsheetChecksheet && $checksheet && !$checksheet->is_complete) {
            return back()->withErrors(['stage' => 'Checksheet tahap ini belum selesai diisi. Progress saat ini: ' . $checksheet->progress . '%. Harap selesaikan checksheet sebelum melanjutkan.']);
        }

        $nextStage = $currentStage + 1;

        // === TAHAP 2: DIS Assembling (termasuk Measurement & Inspection) ===
        // Form inspeksi digital digantikan spreadsheet Measurement bila ada,
        // jadi validasi parts hanya berlaku untuk komponen tanpa spreadsheet.
        if ($currentStage == 2 && !$component->gsheet_measurement_url && !$component->gsheet_subassy_measurement_url) {
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

            // Auto-draft FR untuk part ber-decision Repair (form internal)
            app(FabricationRequestService::class)->createFromInspectionDetails(
                $component->fresh(['inspectionDetails', 'fabricationRequests']),
                auth()->id()
            );
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

            $this->ensureChecksheetForStage($component, $nextStage);

            // Buat log untuk tahapan selanjutnya
            $stageNote = self::STAGE_NAMES[$nextStage] ?? 'Tahap ' . $nextStage;
            $logData = [
                'stage_number' => $nextStage,
                'mechanic_id' => auth()->id(),
                'start_time' => now(),
                'notes' => 'Memulai: ' . $stageNote,
            ];

        // Jika sudah tahap akhir (RFU), langsung tutup lognya
        if ($isFinalCompleted) {
            $logData['end_time'] = now();
            $logData['notes'] = 'Seluruh tahapan overhaul selesai — Komponen Ready for Use (RFU)';
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

        $this->ensureChecksheetForStage($component, $nextStage);

        // Buat log untuk tahapan selanjutnya
        $stageNote = self::STAGE_NAMES[$nextStage] ?? 'Tahap ' . $nextStage;
        $logData = [
            'stage_number' => $nextStage,
            'mechanic_id' => auth()->id(), // Idealnya mencatat yang approve atau tetap mekanik sebelumnya, tapi kita pakai auth() untuk jejak Management yang trigger
            'start_time' => now(),
            'notes' => 'Memulai: ' . $stageNote . ' (Approved)',
        ];

        // Jika sudah tahap akhir (RFU), langsung tutup lognya
        if ($isFinalCompleted) {
            $logData['end_time'] = now();
            $logData['notes'] = 'Seluruh tahapan overhaul selesai — Komponen Ready for Use (RFU) (Approved)';
        }

        $component->overhaulLogs()->create($logData);

        // Saat masuk stage 4/5 (atau tahap lain yang butuh GSheet), pastikan
        // salinan template dijadwalkan — komponen lama sering belum punya URL.
        if (app(ChecksheetGsheetService::class)->hasPendingDuplication($component->fresh())) {
            DuplicateChecksheetGsheets::dispatch($component->comp_id);
        }

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
     * Stage 5 (Test Performance & Painting): unggah foto dokumentasi hasil pengecatan.
     */
    public function uploadPaintingPhotos(Request $request, Component $component)
    {
        if (!auth()->user()->hasAnyRole(['Mechanic', 'Supervisor', 'SuperAdmin'])) {
            return back()->withErrors(['painting' => 'Anda tidak memiliki izin mengunggah foto painting.']);
        }

        $request->validate([
            'photos' => 'required|array|min:1|max:12',
            'photos.*' => 'image|mimes:jpeg,png,jpg,webp|max:10240',
        ]);

        $images = $component->painting_images ?? [];

        foreach ($request->file('photos') as $photo) {
            $images[] = [
                'path' => 'storage/' . $photo->store('painting-photos', 'public'),
                'uploaded_at' => now()->toDateTimeString(),
                'uploaded_by' => auth()->user()->name,
            ];
        }

        $component->update(['painting_images' => $images]);

        return redirect()
            ->to(route('components.show', $component->comp_id) . '#painting-panel')
            ->with('success', count($request->file('photos')) . ' foto painting berhasil diunggah.');
    }

    /**
     * Stage 5 (Test Performance & Painting): hapus satu foto dokumentasi.
     */
    public function deletePaintingPhoto(Request $request, Component $component)
    {
        if (!auth()->user()->hasAnyRole(['Mechanic', 'Supervisor', 'SuperAdmin'])) {
            return back()->withErrors(['painting' => 'Anda tidak memiliki izin menghapus foto painting.']);
        }

        $request->validate(['index' => 'required|integer|min:0']);

        $images = array_values($component->painting_images ?? []);
        $index = $request->integer('index');

        if (!array_key_exists($index, $images)) {
            return back()->withErrors(['painting' => 'Foto tidak ditemukan.']);
        }

        $removed = $images[$index];
        unset($images[$index]);
        $component->update(['painting_images' => array_values($images)]);

        $relative = preg_replace('#^storage/#', '', (string) ($removed['path'] ?? ''));
        if ($relative) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($relative);
        }

        return redirect()
            ->to(route('components.show', $component->comp_id) . '#painting-panel')
            ->with('success', 'Foto painting dihapus.');
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
