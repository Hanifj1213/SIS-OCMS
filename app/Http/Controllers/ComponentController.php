<?php

namespace App\Http\Controllers;

use App\Models\ChecksheetTemplate;
use App\Models\Component;
use App\Jobs\DuplicateChecksheetGsheets;
use App\Models\PartRequest;
use App\Services\ChecksheetGsheetService;
use App\Services\StageTransitionService;
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
     * Daftar semua komponen.
     */
    public function index()
    {
        $components = Component::latest()->get();
        return view('overhauls.index', compact('components'));
    }

    /**
     * Kategori komponen yang valid: daftar bawaan + kategori baru yang
     * ditambahkan Developer lewat tabel gsheet_templates / checksheet_templates,
     * sehingga kategori baru tidak butuh perubahan kode.
     *
     * @return list<string>
     */
    public static function categoryOptions(): array
    {
        $base = [
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

        try {
            $fromGsheet = \App\Models\GsheetTemplate::query()
                ->whereNotNull('major_category')
                ->distinct()
                ->pluck('major_category')
                ->all();
            $fromChecksheet = ChecksheetTemplate::query()
                ->distinct()
                ->pluck('major_category')
                ->all();

            return array_values(array_unique(array_merge($base, $fromGsheet, $fromChecksheet)));
        } catch (\Throwable) {
            return $base;
        }
    }

    /**
     * Form daftarkan komponen baru.
     */
    public function create()
    {
        if (! auth()->user()->canRegisterComponents()) {
            abort(403, 'Anda tidak memiliki izin mendaftarkan komponen baru.');
        }

        return view('overhauls.create', [
            'categories' => self::categoryOptions(),
        ]);
    }

    /**
     * Simpan komponen baru + generate QR Code + buat log awal.
     */
    public function store(Request $request)
    {
        if (! auth()->user()->canRegisterComponents()) {
            abort(403, 'Anda tidak memiliki izin mendaftarkan komponen baru.');
        }

        $validCategories = self::categoryOptions();

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
            'ro_number' => 'nullable|string|max:255',
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
            // Logistik (Manifest & Way Bill digabung satu field; slot lama untuk RO)
            'manifest' => $request->manifest ? trim($request->manifest) : null,
            'ro_number' => $request->ro_number ? trim($request->ro_number) : null,
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

        // Snapshot checksheet Receiving dari template (EGI-spesifik â†’ Generic)
        app(StageTransitionService::class)->ensureChecksheetForStage($component, 1);

        // Duplikasi template Google Sheets dijalankan di LATAR BELAKANG.
        // Ada empat jenis template dan tiap panggilan Apps Script bisa 10â€“20
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
        $component->load(['overhaulLogs.mechanic', 'overhaulLogs.approvalRequester', 'overhaulLogs.approver', 'inspectionDetails', 'partRequests', 'checksheets', 'fabricationRequests']);

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
        app(StageTransitionService::class)->ensureChecksheetForStage($component, $reviewStage ?? $component->current_stage);
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
            'disassemblyTemplateAvailable' => (bool) (
                $gsheetService->templateIdFor($component, 'disassembly')
                || $gsheetService->templateIdFor($component, 'subassy_disassembly')
            ),
            'measurementTemplateAvailable' => (bool) (
                $gsheetService->templateIdFor($component, 'measurement')
                || $gsheetService->templateIdFor($component, 'subassy_measurement')
            ),
            'stageTimeMetrics' => $stageTimeMetrics,
        ]);
    }

    /**
     * Proses perpindahan tahapan.
     */
    public function updateStage(Request $request, Component $component)
    {
        // RBAC: hanya Mechanic, Supervisor, SuperAdmin yang boleh proses tahap
        if (! auth()->user()->canOperateOverhaul()) {
            return back()->withErrors(['stage' => 'Anda tidak memiliki izin untuk memproses tahapan.']);
        }

        $transition = app(StageTransitionService::class);

        $result = $transition->inTransaction(function () use ($request, $component, $transition) {
            $locked = $transition->lockComponent($component);

            $currentStage = $locked->current_stage;
            $nextStage = $currentStage + 1;

            // Cegah stage melebihi 7 (validasi ulang pada state terkunci)
            if ($currentStage >= 7) {
                return ['error' => 'Komponen sudah mencapai tahap akhir (RFU).'];
            }

            if ($locked->is_waiting_approval) {
                return ['error' => 'Komponen ini sedang menunggu approval Group Leader / Supervisor.'];
            }

            // Cek apakah checksheet tahap ini sudah diisi 100%.
            // Dikecualikan bila tahap ini memakai checksheet Google Sheets
            // (spreadsheet menggantikan checksheet internal, progressnya tidak
            // terlacak di database).
            // Stage 2 memakai spreadsheet bila komponen punya gsheet_url
            // (Engine mainline / Powertrain Control Valve dkk.).
            // Stage 4 (Assembly) & 5 (Test Bench) juga memakai spreadsheet bila
            // salinan GSheet-nya tersedia.
            // Tahap 2 resmi memakai GSheet (Disassembly + Measurement), bukan checksheet internal.
            $usesGsheetChecksheet = ($currentStage == 2)
                || ($currentStage == 4 && (bool) $locked->gsheet_assembly_url)
                || ($currentStage == 5 && (bool) $locked->gsheet_testbench_url);

            $checksheet = $locked->checksheets()->where('stage_number', $currentStage)->first();
            if (!$usesGsheetChecksheet && $checksheet && !$checksheet->is_complete) {
                return ['error' => 'Checksheet tahap ini belum selesai diisi. Progress saat ini: ' . $checksheet->progress . '%. Harap selesaikan checksheet sebelum melanjutkan.'];
            }

            // Tambahkan catatan mekanik ke log saat ini
            if ($request->filled('remarks')) {
                $currentLog = $locked->overhaulLogs()
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
                $transition->requestApproval($locked, auth()->id());

                return ['waiting_approval' => true, 'next_stage' => $nextStage];
            }

            // Auto-transition (tidak perlu approval)
            return ['final_completed' => $transition->advance($locked, auth()->id())];
        });

        if (isset($result['error'])) {
            return back()->withErrors(['stage' => $result['error']]);
        }

        if (isset($result['waiting_approval'])) {
            return redirect()->route('components.show', $component->comp_id)
                ->with('success', 'Progress tahap ' . $component->current_stage . ' selesai. Menunggu approval Group Leader / Supervisor untuk lanjut ke Tahap ' . $result['next_stage'] . '.');
        }

        $isFinalCompleted = $result['final_completed'];

        return redirect()->route('components.show', $component->comp_id)
            ->with('success', 'Tahap ' . $component->current_stage . ' selesai! Komponen langsung berlanjut ke ' . ($isFinalCompleted ? 'status Ready for Use (RFU)' : 'Tahap ' . ($component->current_stage + 1)));
    }

    /**
     * Setujui transisi tahap oleh Management
     */
    public function approveStage(Component $component)
    {
        if (! auth()->user()->canApproveStages()) {
            return back()->withErrors(['approval' => 'Hanya Group Leader, Supervisor, atau jabatan head yang dapat memberikan approval.']);
        }

        $transition = app(StageTransitionService::class);
        $approved = false;
        $isFinalCompleted = false;
        $nextStage = $component->current_stage + 1;

        $transition->inTransaction(function () use ($component, $transition, &$approved, &$isFinalCompleted) {
            $locked = $transition->lockComponent($component);

            if (!$locked->is_waiting_approval) {
                return; // Duplicate approve — state terkunci, tidak ada transisi kedua
            }

            // Naikkan stage + catat jejak approver (mechanic_id log baru = auth,
            // sebagai jejak Management yang men-trigger transisi)
            $isFinalCompleted = $transition->advance($locked, auth()->id(), approvedBy: auth()->id());
            $approved = true;
        });

        if (!$approved) {
            return back()->withErrors(['approval' => 'Komponen ini tidak sedang menunggu approval.']);
        }

        // Side effect queue di luar transaksi, hanya setelah commit sukses
        $component->refresh();

        // Saat masuk stage 4/5 (atau tahap lain yang butuh GSheet), pastikan
        // salinan template dijadwalkan — komponen lama sering belum punya URL.
        if (app(ChecksheetGsheetService::class)->hasPendingDuplication($component)) {
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
        if (! auth()->user()->canApproveStages()) {
            return back()->withErrors(['approval' => 'Hanya Group Leader, Supervisor, atau jabatan head yang dapat menolak approval.']);
        }

        app(StageTransitionService::class)->inTransaction(function () use ($component) {
            app(StageTransitionService::class)->reject($component, auth()->user()->name);
        });

        return redirect()->route('components.show', $component->comp_id)
            ->with('success', 'Approval ditolak. Komponen dikembalikan ke mekanik pada tahap ' . $component->current_stage . '.');
    }

    /**
     * Form edit komponen â€” Developer & SuperAdmin.
     */
    public function edit(Component $component)
    {
        if (! auth()->user()->canManageComponents()) {
            abort(403, 'Hanya Developer atau SuperAdmin yang dapat mengedit komponen.');
        }

        return view('overhauls.edit', [
            'comp' => $component,
            'categories' => self::categoryOptions(),
        ]);
    }

    /**
     * Simpan perubahan data komponen (identitas, logistik, link GSheet).
     */
    public function update(Request $request, Component $component)
    {
        if (! auth()->user()->canManageComponents()) {
            abort(403, 'Hanya Developer atau SuperAdmin yang dapat mengedit komponen.');
        }

        $validated = $request->validate([
            // Data Unit
            'egi' => 'required|string|max:100',
            'unit_code' => 'required|string|max:100',
            'unit_serial_no' => 'nullable|string|max:100',
            'site_district' => 'required|string|max:100',
            // Data Komponen
            'major_category' => 'required|string|in:' . implode(',', self::categoryOptions()),
            'serial_number' => 'required|string|max:100|unique:components,serial_number,' . $component->comp_id . ',comp_id',
            'pn_assy' => 'nullable|string|max:100',
            'status_ovh' => 'required|string|in:SCHEDULE,UNSCHEDULE',
            'core_category' => 'nullable|string|in:A,B,C',
            // Informasi Operasional
            'smr' => 'nullable|integer|min:0',
            'life_time' => 'nullable|integer|min:0',
            'date_defitted' => 'nullable|date',
            // Logistik
            'manifest' => 'nullable|string|max:255',
            'ro_number' => 'nullable|string|max:255',
            'date_delivery' => 'nullable|date',
            // Link Google Sheets (boleh dikosongkan agar diduplikasi ulang)
            'gsheet_url' => 'nullable|url|max:500',
            'gsheet_measurement_url' => 'nullable|url|max:500',
            'gsheet_subassy_disassembly_url' => 'nullable|url|max:500',
            'gsheet_subassy_measurement_url' => 'nullable|url|max:500',
            'gsheet_sdr_url' => 'nullable|url|max:500',
            'gsheet_assembly_url' => 'nullable|url|max:500',
            'gsheet_testbench_url' => 'nullable|url|max:500',
        ]);

        $component->update([
            'egi' => strtoupper(trim($validated['egi'])),
            'model_type' => strtoupper(trim($validated['egi'])), // backward compat
            'unit_code' => strtoupper(trim($validated['unit_code'])),
            'unit_serial_no' => filled($validated['unit_serial_no'] ?? null) ? strtoupper(trim($validated['unit_serial_no'])) : null,
            'site_district' => trim($validated['site_district']),
            'major_category' => $validated['major_category'],
            'component_model' => $validated['major_category'],
            'serial_number' => strtoupper(trim($validated['serial_number'])),
            'pn_assy' => filled($validated['pn_assy'] ?? null) ? strtoupper(trim($validated['pn_assy'])) : null,
            'status_ovh' => $validated['status_ovh'],
            'core_category' => $validated['core_category'] ?? null,
            'smr' => $validated['smr'] ?? null,
            'life_time' => $validated['life_time'] ?? null,
            'date_defitted' => $validated['date_defitted'] ?? null,
            'manifest' => filled($validated['manifest'] ?? null) ? trim($validated['manifest']) : null,
            'ro_number' => filled($validated['ro_number'] ?? null) ? trim($validated['ro_number']) : null,
            'date_delivery' => $validated['date_delivery'] ?? null,
            'gsheet_url' => $validated['gsheet_url'] ?? null,
            'gsheet_measurement_url' => $validated['gsheet_measurement_url'] ?? null,
            'gsheet_subassy_disassembly_url' => $validated['gsheet_subassy_disassembly_url'] ?? null,
            'gsheet_subassy_measurement_url' => $validated['gsheet_subassy_measurement_url'] ?? null,
            'gsheet_sdr_url' => $validated['gsheet_sdr_url'] ?? null,
            'gsheet_assembly_url' => $validated['gsheet_assembly_url'] ?? null,
            'gsheet_testbench_url' => $validated['gsheet_testbench_url'] ?? null,
        ]);

        // Link GSheet yang dikosongkan akan otomatis diduplikasi ulang dari
        // template bila tersedia (dipicu saat halaman detail dibuka).
        return redirect()->route('components.show', $component->comp_id)
            ->with('success', 'Komponen "' . $component->serial_number . '" berhasil diperbarui.');
    }

    /**
     * Hapus komponen beserta seluruh data & file terkait â€” Developer & SuperAdmin.
     */
    public function destroy(Component $component)
    {
        if (! auth()->user()->canManageComponents()) {
            abort(403, 'Hanya Developer atau SuperAdmin yang dapat menghapus komponen.');
        }

        $serial = $component->serial_number;

        // Hapus file fisik terkait (best-effort; record DB anak terhapus via FK cascade)
        if ($component->qr_code_path && is_file(public_path($component->qr_code_path))) {
            @unlink(public_path($component->qr_code_path));
        }

        $storagePaths = [];
        foreach (array_merge(
            array_values($component->painting_images ?? []),
            array_values($component->assembly_documents ?? [])
        ) as $file) {
            $storagePaths[] = (string) ($file['path'] ?? '');
        }
        $storagePaths[] = (string) ($component->mol_document_path ?? '');

        foreach ($storagePaths as $path) {
            $relative = preg_replace('#^storage/#', '', $path);
            if ($relative) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($relative);
            }
        }

        $component->delete();

        return redirect()->route('components.index')
            ->with('success', 'Komponen "' . $serial . '" beserta seluruh datanya berhasil dihapus.');
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



