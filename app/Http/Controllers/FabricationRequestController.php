<?php

namespace App\Http\Controllers;

use App\Models\Component;
use App\Models\FabricationRequest;
use App\Services\FabricationRequestService;
use App\Services\FrAttachmentResolver;
use App\Services\FrAttachmentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class FabricationRequestController extends Controller
{
    public function __construct(
        private FabricationRequestService $frService
    ) {}

    private function authorizeMechanic(): ?\Illuminate\Http\JsonResponse
    {
        if (! auth()->user()?->canOperateOverhaul()) {
            return response()->json(['ok' => false, 'message' => 'Unauthorized'], 403);
        }

        return null;
    }

    public function index(Component $component)
    {
        if ($denied = $this->authorizeMechanic()) {
            return $denied;
        }

        $items = $component->fabricationRequests()
            ->latest('fr_id')
            ->get()
            ->map(fn (FabricationRequest $fr) => [
                'fr_id' => $fr->fr_id,
                'fr_number' => $fr->fr_number,
                'part_number' => $fr->part_number,
                'part_name' => $fr->part_name,
                'qty' => $fr->qty,
                'work_type' => $fr->work_type,
                'work_type_label' => $fr->workTypeLabel(),
                'source' => $fr->source,
                'status' => $fr->status,
                'status_label' => $fr->statusLabel(),
                'instruction' => $fr->instruction,
                'pdf_url' => route('components.fr.pdf', [$component->comp_id, $fr->fr_id]),
                'edit_url' => route('components.fr.edit', [$component->comp_id, $fr->fr_id]),
                'created_at' => $fr->created_at?->format('d/m/Y H:i'),
            ]);

        return response()->json(['ok' => true, 'items' => $items]);
    }

    public function scan(Component $component)
    {
        if ($denied = $this->authorizeMechanic()) {
            return $denied;
        }

        // Baca multi-tab GSheet + retry cold-start Apps Script sering > 30 detik.
        set_time_limit(300);
        ini_set('max_execution_time', '300');

        $result = $this->frService->scanCandidates($component);

        $candidates = $result['candidates'] ?? [];
        $prCandidates = $result['part_request_candidates'] ?? [];

        $createdFr = $candidates !== []
            ? $this->frService->createFromCandidates($component, $candidates, auth()->id())
            : [];

        $createdPr = $prCandidates !== []
            ? $this->frService->createPartRequestsFromCandidates($component, $prCandidates)
            : [];

        $sync = $result['sync'] ?? ['removed_fr' => [], 'removed_pr' => [], 'blocked' => []];
        $removedFrCount = count($sync['removed_fr'] ?? []);
        $removedPrCount = count($sync['removed_pr'] ?? []);
        $blockedCount = count($sync['blocked'] ?? []);

        $messages = [];
        if ($removedFrCount > 0) {
            $messages[] = $removedFrCount . ' FR dihapus/diganti';
        }
        if ($removedPrCount > 0) {
            $messages[] = $removedPrCount . ' MOL dihapus/diganti';
        }
        if ($createdFr !== []) {
            $messages[] = count($createdFr) . ' FR baru';
        }
        if ($createdPr !== []) {
            $messages[] = count($createdPr) . ' MOL baru';
        }

        $msg = $messages !== []
            ? 'Scan selesai: ' . implode(', ', $messages) . '.'
            : 'Tidak ada perubahan FR atau MOL.';

        if ($blockedCount > 0) {
            $msg .= " {$blockedCount} item tidak disentuh (sudah dicetak/diproses gudang).";
        }

        return response()->json([
            'ok' => true,
            'message' => $msg,
            'created_fr' => collect($createdFr)->map(fn (FabricationRequest $fr) => [
                'fr_id' => $fr->fr_id,
                'fr_number' => $fr->fr_number,
                'part_name' => $fr->part_name,
                'pdf_url' => route('components.fr.pdf', [$component->comp_id, $fr->fr_id]),
                'edit_url' => route('components.fr.edit', [$component->comp_id, $fr->fr_id]),
            ]),
            'created_pr' => collect($createdPr)->map(fn ($pr) => [
                'req_id' => $pr->req_id,
                'part_name' => $pr->part_name,
                'qty' => $pr->qty,
            ]),
            'skipped' => $result['skipped'],
            'sync' => $sync,
            'gsheet_error' => $result['gsheet_error'],
            'gsheet_warning' => $result['gsheet_warning'] ?? null,
            'gsheet_sheet' => $result['gsheet_sheet'],
            'scan_profile' => $result['scan_profile'],
            'scan_profile_label' => $result['scan_profile_label'],
            'total_fr' => $component->fabricationRequests()->count(),
            'total_pr' => $component->partRequests()->count(),
        ]);
    }

    public function store(Request $request, Component $component)
    {
        if ($denied = $this->authorizeMechanic()) {
            return $denied;
        }

        $validated = $request->validate([
            'items' => 'nullable|array',
            'items.*.part_name' => 'required_with:items|string|max:255',
            'items.*.part_number' => 'nullable|string|max:255',
            'items.*.section' => 'nullable|string|max:255',
            'items.*.qty' => 'nullable|integer|min:1',
            'items.*.work_type' => 'nullable|in:repair,fabrikasi,modifikasi',
            'items.*.instruction' => 'nullable|string|max:5000',
            'items.*.source' => 'nullable|in:form,gsheet,manual',
            'part_request_items' => 'nullable|array',
            'part_request_items.*.part_name' => 'required_with:part_request_items|string|max:255',
            'part_request_items.*.section' => 'nullable|string|max:255',
            'part_request_items.*.qty' => 'nullable|integer|min:1',
        ]);

        $frItems = $validated['items'] ?? [];
        $prItems = $validated['part_request_items'] ?? [];

        if ($frItems === [] && $prItems === []) {
            return response()->json([
                'ok' => false,
                'message' => 'Pilih minimal satu kandidat FR atau Part Request.',
            ], 422);
        }

        $created = $frItems !== []
            ? $this->frService->createFromCandidates($component, $frItems, auth()->id())
            : [];

        $createdPr = $prItems !== []
            ? $this->frService->createPartRequestsFromCandidates($component, $prItems)
            : [];

        if ($created === [] && $createdPr === []) {
            return response()->json([
                'ok' => false,
                'message' => 'Tidak ada FR/PR baru yang dibuat (part mungkin sudah ada).',
            ], 422);
        }

        $messages = [];
        if ($created !== []) {
            $messages[] = count($created) . ' Fabrication Request';
        }
        if ($createdPr !== []) {
            $messages[] = count($createdPr) . ' Part Request';
        }

        return response()->json([
            'ok' => true,
            'message' => implode(' + ', $messages) . ' berhasil dibuat.',
            'created' => collect($created)->map(fn (FabricationRequest $fr) => [
                'fr_id' => $fr->fr_id,
                'fr_number' => $fr->fr_number,
                'part_name' => $fr->part_name,
                'pdf_url' => route('components.fr.pdf', [$component->comp_id, $fr->fr_id]),
                'edit_url' => route('components.fr.edit', [$component->comp_id, $fr->fr_id]),
            ]),
            'created_part_requests' => collect($createdPr)->map(fn ($pr) => [
                'req_id' => $pr->req_id,
                'part_name' => $pr->part_name,
                'qty' => $pr->qty,
            ]),
        ]);
    }

    /**
     * Form FR kosong / prefilled dari kandidat scan (query string), belum tersimpan.
     */
    public function create(Request $request, Component $component)
    {
        if ($denied = $this->authorizeMechanic()) {
            return $denied;
        }

        $candidate = [
            'part_name' => (string) $request->query('part_name', ''),
            'part_number' => (string) $request->query('part_number', ''),
            'section' => (string) $request->query('section', ''),
            'qty' => max(1, (int) $request->query('qty', 1)),
            'work_type' => in_array($request->query('work_type'), ['repair', 'fabrikasi', 'modifikasi'], true)
                ? $request->query('work_type')
                : 'repair',
            'instruction' => (string) $request->query('instruction', ''),
            'source' => in_array($request->query('source'), ['form', 'gsheet', 'manual'], true)
                ? $request->query('source')
                : 'manual',
        ];

        // Nama variabel 'comp' (bukan 'component') karena di dalam
        // <x-app-layout> Blade menimpa $component dengan instance komponennya.
        return view('fr.form', [
            'comp' => $component,
            'fr' => null,
            'candidate' => $candidate,
        ]);
    }

    /**
     * Simpan satu FR dari form PLO/09/F-021 (bukan dari daftar kandidat).
     */
    public function storeSingle(Request $request, Component $component)
    {
        if ($denied = $this->authorizeMechanic()) {
            return $denied;
        }

        $validated = $request->validate($this->formRules());
        $types = $this->workTypesFrom($validated);

        $manualNumber = trim((string) ($validated['fr_number'] ?? ''));
        $manualNumber = $manualNumber !== '' ? $manualNumber : null;

        $fr = $this->frService->createDraft(
            $component,
            [
                'part_number' => $validated['part_number'] ?? null,
                'part_name' => $validated['part_name'],
                'section' => ($validated['section'] ?? '') ?: null,
                'qty' => $validated['qty'],
                'work_type' => $types['work_type'],
                'instruction' => $validated['instruction'] ?? null,
                'source' => $validated['source'] ?? 'manual',
            ],
            auth()->id(),
            $manualNumber,
        );

        $extra = $this->plofieldsFrom($request, $validated, null)
            + ['work_types' => $types['work_types'], 'signatures' => app(FrAttachmentService::class)->signaturesFrom($request, null)];

        if ($manualNumber !== null) {
            $this->frService->syncSequenceFromManualNumber($manualNumber);
        }

        $fr->update($extra);

        return redirect()->route('components.fr.edit', [$component->comp_id, $fr->fr_id])
            ->with('success', 'Fabrication Request ' . $fr->fr_number . ' berhasil dibuat. Silakan unduh PDF bila sudah sesuai.');
    }

    public function edit(Component $component, FabricationRequest $fr)
    {
        // RBAC: hanya pelaksana yang boleh mengubah dokumen FR.
        if (! auth()->user()?->canOperateOverhaul()) {
            abort(403, 'Hanya Mechanic/Supervisor/SuperAdmin yang boleh mengubah FR.');
        }

        if ($fr->comp_id !== $component->comp_id) {
            abort(404);
        }

        return view('fr.form', [
            'comp' => $component,
            'fr' => $fr,
            'candidate' => [],
        ]);
    }

    public function update(Request $request, Component $component, FabricationRequest $fr)
    {
        // RBAC: hanya pelaksana yang boleh mengubah dokumen FR.
        if (! auth()->user()?->canOperateOverhaul()) {
            abort(403, 'Hanya Mechanic/Supervisor/SuperAdmin yang boleh mengubah FR.');
        }

        if ($fr->comp_id !== $component->comp_id) {
            abort(404);
        }

        $validated = $request->validate($this->formRules($fr->fr_id));
        $types = $this->workTypesFrom($validated);

        $data = [
            'work_type' => $types['work_type'],
            'work_types' => $types['work_types'],
            'signatures' => app(FrAttachmentService::class)->signaturesFrom($request, $fr),
            'part_number' => $validated['part_number'] ?? null,
            'part_name' => $validated['part_name'],
            'qty' => $validated['qty'],
            'instruction' => $validated['instruction'] ?? null,
        ] + $this->plofieldsFrom($request, $validated, $fr);

        // Nomor FR boleh disunting, tapi jangan sampai dikosongkan.
        if (trim((string) ($validated['fr_number'] ?? '')) !== '') {
            $newNumber = trim($validated['fr_number']);
            if ($newNumber !== $fr->fr_number) {
                $data['fr_number'] = $newNumber;
                $this->frService->syncSequenceFromManualNumber($newNumber);
            }
        }

        $fr->update($data);

        return redirect()->route('components.show', $component->comp_id)
            ->with('success', 'Form Fabrication Request (PLO/09/F-021) ' . $fr->fr_number . ' berhasil diperbarui.');
    }

    /**
     * Aturan validasi form PLO/09/F-021 (dipakai create & update).
     *
     * @return array<string, string>
     */
    private function formRules(?int $ignoreFrId = null): array
    {

        return [
            // Form asli memakai kotak centang: boleh lebih dari satu jenis.
            // 'work_type' tetap diterima demi kompatibilitas pemanggil lama.
            'work_type' => 'nullable|in:repair,fabrikasi,modifikasi,others',
            'work_types' => 'nullable|array',
            'work_types.*' => 'in:repair,fabrikasi,modifikasi,others',
            'address' => 'nullable|string|max:255',
            'form_no' => 'nullable|string|max:60',
            'sop_no' => 'nullable|string|max:60',
            'form_owner' => 'nullable|string|max:120',
            'form_revision' => 'nullable|string|max:20',
            'image_2' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            // Identitas unit boleh disunting walau terisi otomatis dari komponen
            'unit_model' => 'nullable|string|max:255',
            'component_model' => 'nullable|string|max:255',
            'unit_code' => 'nullable|string|max:255',
            // Nomor FR boleh disunting; kosong = sistem memberi nomor berikutnya.
            // Harus unik karena dipakai sebagai identitas dokumen.
            'fr_number' => [
                'nullable', 'string', 'max:60',
                Rule::unique('fabrication_requests', 'fr_number')->ignore($ignoreFrId, 'fr_id'),
            ],
            // Daftar gambar "Gambar & Dimensi": jumlahnya bebas.
            // 'path' = gambar yang sudah tersimpan, 'data' = unggahan baru (data URL).
            'images' => 'nullable|array|max:20',
            'images.*.path' => 'nullable|string|max:255',
            'images.*.data' => 'nullable|string',
            'images.*.x' => 'nullable|numeric|between:-20,120',
            'images.*.y' => 'nullable|numeric|between:-20,120',
            'images.*.w' => 'nullable|numeric|between:5,100',
            // Posisi & ukuran gambar tanda tangan
            'signature_layout' => 'nullable|array',
            'signature_layout.*.x' => 'nullable|numeric|between:-20,120',
            'signature_layout.*.y' => 'nullable|numeric|between:-20,120',
            'signature_layout.*.w' => 'nullable|numeric|between:5,100',
            // Anotasi vektor kanvas "Gambar & Dimensi" (garis/kuas/konektor/teks)
            // dikirim sebagai JSON dari toolbar; divalidasi saat decode.
            'annotations_json' => 'nullable|string|max:65535',
            // Format lama sebelum editor SVG disatukan. Tetap diterima agar
            // request/test lama dan proses edit dokumen lama tidak rusak.
            'annotations_present' => 'nullable|boolean',
            'annotations' => 'nullable|array|max:200',
            'signatures.*.remove_image' => 'nullable|boolean',
            // Kolom lama (dua gambar tetap) — tetap diterima demi kompatibilitas
            'image_layout' => 'nullable|array',
            'image_layout.*.x' => 'nullable|numeric|between:-20,120',
            'image_layout.*.y' => 'nullable|numeric|between:-20,120',
            'image_layout.*.w' => 'nullable|numeric|between:5,100',
            'signatures' => 'nullable|array',
            'signatures.*.name' => 'nullable|string|max:120',
            'signatures.*.date' => 'nullable|date',
            'signatures.*.image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'part_number' => 'nullable|string|max:255',
            'part_name' => 'required|string|max:255',
            'section' => 'nullable|string|max:255',
            'source' => 'nullable|in:form,gsheet,manual',
            'qty' => 'required|integer|min:1',
            'instruction' => 'nullable|string|max:5000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'ro_number' => 'nullable|string|max:255',
            'pr_number' => 'nullable|string|max:255',
            'request_date' => 'nullable|date',
            'estimation_date' => 'nullable|date',
            'location_site' => 'nullable|string|max:255',
            'work_order_for' => 'nullable|string|max:255',
            'sent_to' => 'nullable|string|max:255',
            'attn' => 'nullable|string|max:255',
            'brand' => 'nullable|string|max:255',
            'unit_price' => 'nullable|numeric|min:0',
            'labour_cost' => 'nullable|numeric|min:0',
            'note' => 'nullable|string|max:2000',
        ];
    }

    /**
     * Kolom administratif form PLO/09/F-021 + penanganan unggahan sketsa.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function plofieldsFrom(Request $request, array $validated, ?FabricationRequest $fr = null): array
    {
        $data = [];

        foreach ([
            'ro_number', 'pr_number', 'request_date', 'estimation_date',
            'location_site', 'work_order_for', 'sent_to', 'address', 'attn',
            'brand', 'unit_price', 'labour_cost', 'note',
            'form_no', 'sop_no', 'form_owner', 'form_revision',
            'unit_model', 'component_model', 'unit_code',
        ] as $field) {
            if (array_key_exists($field, $validated)) {
                $data[$field] = $validated[$field] === '' ? null : $validated[$field];
            }
        }

        $attachments = app(FrAttachmentService::class);
        $resolver = app(FrAttachmentResolver::class);

        if ($request->hasFile('image')) {
            $stored = $request->file('image')->store('fr-sketches', 'public');
            $candidate = 'storage/'.$stored;
            $data['image_path'] = $resolver->normalizeClientPath($candidate) ?? null;
            if ($data['image_path'] === null) {
                Storage::disk('public')->delete($stored);
            }
        }

        if ($request->hasFile('image_2')) {
            $stored = $request->file('image_2')->store('fr-sketches', 'public');
            $candidate = 'storage/'.$stored;
            $data['image_path_2'] = $resolver->normalizeClientPath($candidate) ?? null;
            if ($data['image_path_2'] === null) {
                Storage::disk('public')->delete($stored);
            }
        }

        if (isset($validated['image_layout'])) {
            $data['image_layout'] = $attachments->imageLayoutFrom($validated['image_layout']);
        }

        if (array_key_exists('images', $validated)) {
            $data['images'] = $attachments->imagesFrom((array) $validated['images'], $fr);
        }

        if (isset($validated['signature_layout'])) {
            $data['signature_layout'] = $attachments->signatureLayoutFrom($validated['signature_layout']);
        }

        if (array_key_exists('annotations_json', $validated)) {
            $data['annotations'] = $this->annotationsFrom($validated['annotations_json']);
        } elseif (array_key_exists('annotations_present', $validated)) {
            $data['annotations'] = $attachments->annotationsFrom((array) ($validated['annotations'] ?? []));
        }

        return $data;
    }

    /**
     * Decode & sanitasi daftar anotasi dari toolbar. Hanya tipe yang dikenal
     * yang disimpan, koordinat dikunci 0..100 agar aman dirender ulang.
     *
     * @return list<array<string, mixed>>
     */
    private function annotationsFrom(?string $json): array
    {
        $decoded = $json ? json_decode($json, true) : null;
        if (!is_array($decoded)) {
            return [];
        }

        $out = [];
        foreach ($decoded as $a) {
            if (!is_array($a)) {
                continue;
            }
            // Versi awal memakai underscore (`double_arrow`); versi toolbar
            // sekarang memakai hyphen. Normalisasi supaya data lama tidak
            // hilang saat FR lama diedit lalu disimpan ulang.
            $type = str_replace('_', '-', (string) ($a['type'] ?? ''));
            $storedType = $type === 'double-arrow' ? 'double_arrow' : $type;
            $color = is_string($a['color'] ?? null) && preg_match('/^#[0-9a-fA-F]{3,8}$/', $a['color'])
                ? $a['color'] : '#dc2626';
            $stroke = max(1, min(20, (float) ($a['stroke'] ?? 2)));
            $base = ['id' => (int) ($a['id'] ?? 0), 'type' => $storedType, 'color' => $color, 'stroke' => $stroke];
            $pt = fn ($v) => max(0, min(100, (float) $v));

            if ($type === 'text') {
                $text = trim((string) ($a['text'] ?? ''));
                if ($text === '') {
                    continue;
                }
                $out[] = $base + [
                    'x' => $pt($a['x'] ?? 0),
                    'y' => $pt($a['y'] ?? 0),
                    'text' => mb_substr($text, 0, 200),
                    'font_size' => max(2, min(15, (float) ($a['font_size'] ?? $a['size'] ?? 5))),
                ];
            } elseif ($type === 'brush' && is_array($a['points'] ?? null) && count($a['points']) >= 2) {
                $points = [];
                foreach (array_slice($a['points'], 0, 400) as $p) {
                    if (is_array($p) && isset($p['x'], $p['y'])) {
                        $points[] = ['x' => $pt($p['x']), 'y' => $pt($p['y'])];
                    }
                }
                if (count($points) >= 2) {
                    $out[] = $base + ['points' => $points];
                }
            } elseif (in_array($type, ['line', 'arrow', 'connector', 'double-arrow'], true)) {
                $out[] = $base + [
                    'x1' => $pt($a['x1'] ?? 0), 'y1' => $pt($a['y1'] ?? 0),
                    'x2' => $pt($a['x2'] ?? 0), 'y2' => $pt($a['y2'] ?? 0),
                ];
            }
        }

        return $out;
    }

    /**
     * Jenis pekerjaan tercentang. Form baru mengirim `work_types[]`; permintaan
     * lama (panel scan, hook Stage 2) masih mengirim `work_type` tunggal.
     *
     * @param  array<string, mixed>  $validated
     * @return array{work_type: string, work_types: list<string>}
     */
    private function workTypesFrom(array $validated): array
    {
        $types = array_values(array_intersect(
            FabricationRequest::WORK_TYPES,
            (array) ($validated['work_types'] ?? [])
        ));

        if ($types === []) {
            $single = $validated['work_type'] ?? 'repair';
            $types = [in_array($single, FabricationRequest::WORK_TYPES, true) ? $single : 'repair'];
        }

        return [
            // Kolom lama menyimpan pilihan pertama agar tampilan daftar & PDF
            // lama tetap menampilkan sesuatu yang benar.
            'work_type' => $types[0],
            'work_types' => $types,
        ];
    }

    public function updateStatus(Request $request, Component $component, FabricationRequest $fr)
    {
        if ($fr->comp_id !== $component->comp_id) {
            return response()->json(['ok' => false, 'message' => 'FR tidak cocok dengan komponen'], 404);
        }

        if ($denied = $this->authorizeMechanic()) {
            return $denied;
        }

        $validated = $request->validate([
            'status' => 'required|in:draft,printed,done',
            'completion_notes' => 'nullable|string|max:1000',
        ]);

        $status = $validated['status'];
        $data = ['status' => $status];

        if ($status === 'done') {
            $data['completed_at'] = now();
            if (isset($validated['completion_notes'])) {
                $data['completion_notes'] = $validated['completion_notes'];
            }
        } elseif ($status === 'draft') {
            $data['completed_at'] = null;
        }

        $fr->update($data);

        return response()->json([
            'ok' => true,
            'message' => 'Status FR ' . $fr->fr_number . ' diperbarui ke ' . $fr->statusLabel(),
            'status' => $fr->status,
            'status_label' => $fr->statusLabel(),
            'completed_at' => $fr->completed_at?->format('d/m/Y H:i'),
        ]);
    }

    public function pdf(Component $component, FabricationRequest $fr)
    {
        if ($fr->comp_id !== $component->comp_id) {
            abort(404);
        }

        $component->load([]);
        $fr->load('creator');

        if (auth()->check() && auth()->user()->canOperateOverhaul()) {
            if ($fr->status === 'draft') {
                $fr->update(['status' => 'printed']);
            }
        }

        $resolver = app(FrAttachmentResolver::class);
        $signatureFiles = [];
        foreach (array_keys(FabricationRequest::SIGNATURE_ROLES) as $role) {
            $signatureFiles[$role] = $resolver->resolveSignatureImageForPdf($fr, $role);
        }

        // Form PLO/09/F-021 asli dicetak A4 landscape.
        $pdf = Pdf::loadView('fr.pdf', [
            'component' => $component,
            'fr' => $fr,
            'pdfImages' => $resolver->resolveImagesForPdf($fr),
            'signatureFiles' => $signatureFiles,
        ]);
        $pdf->setPaper('a4', 'landscape');

        $filename = str_replace(['/', '\\'], '_', $fr->fr_number) . '.pdf';

        return $pdf->stream($filename);
    }
}
