<?php

namespace App\Http\Controllers;

use App\Models\Component;
use App\Models\FabricationRequest;
use App\Services\FabricationRequestService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class FabricationRequestController extends Controller
{
    public function __construct(
        private FabricationRequestService $frService
    ) {}

    private function authorizeMechanic(): ?\Illuminate\Http\JsonResponse
    {
        if (!auth()->user()->hasAnyRole(['Mechanic', 'Supervisor', 'SuperAdmin'])) {
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

        $result = $this->frService->scanCandidates($component);

        $candidates = $result['candidates'] ?? [];
        $prCandidates = $result['part_request_candidates'] ?? [];

        $createdFr = $candidates !== []
            ? $this->frService->createFromCandidates($component, $candidates, auth()->id())
            : [];

        $createdPr = $prCandidates !== []
            ? $this->frService->createPartRequestsFromCandidates($component, $prCandidates)
            : [];

        $messages = [];
        if ($createdFr !== []) {
            $messages[] = count($createdFr) . ' FR';
        }
        if ($createdPr !== []) {
            $messages[] = count($createdPr) . ' Part Request (MOL)';
        }

        $msg = $messages !== []
            ? implode(' + ', $messages) . ' berhasil dibuat.'
            : 'Tidak ada FR atau MOL baru yang dibuat.';

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
            'gsheet_error' => $result['gsheet_error'],
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
            auth()->id()
        );

        $extra = $this->plofieldsFrom($request, $validated)
            + ['work_types' => $types['work_types'], 'signatures' => $this->signaturesFrom($request, null)];

        // Nomor FR boleh ditulis manual; kosong = nomor otomatis dari service.
        if (trim((string) ($validated['fr_number'] ?? '')) !== '') {
            $extra['fr_number'] = trim($validated['fr_number']);
        }

        $fr->update($extra);

        return redirect()->route('components.fr.edit', [$component->comp_id, $fr->fr_id])
            ->with('success', 'Fabrication Request ' . $fr->fr_number . ' berhasil dibuat. Silakan unduh PDF bila sudah sesuai.');
    }

    public function edit(Component $component, FabricationRequest $fr)
    {
        // RBAC: hanya pelaksana yang boleh mengubah dokumen FR.
        if (!auth()->user()->hasAnyRole(['Mechanic', 'Supervisor', 'SuperAdmin'])) {
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
        if (!auth()->user()->hasAnyRole(['Mechanic', 'Supervisor', 'SuperAdmin'])) {
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
            'signatures' => $this->signaturesFrom($request, $fr),
            'part_number' => $validated['part_number'] ?? null,
            'part_name' => $validated['part_name'],
            'qty' => $validated['qty'],
            'instruction' => $validated['instruction'] ?? null,
        ] + $this->plofieldsFrom($request, $validated);

        // Nomor FR boleh disunting, tapi jangan sampai dikosongkan.
        if (trim((string) ($validated['fr_number'] ?? '')) !== '') {
            $data['fr_number'] = trim($validated['fr_number']);
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
    private function plofieldsFrom(Request $request, array $validated): array
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

        if ($request->hasFile('image')) {
            $data['image_path'] = 'storage/' . $request->file('image')->store('fr-sketches', 'public');
        }

        if ($request->hasFile('image_2')) {
            $data['image_path_2'] = 'storage/' . $request->file('image_2')->store('fr-sketches', 'public');
        }

        if (isset($validated['image_layout'])) {
            $data['image_layout'] = $this->imageLayoutFrom($validated['image_layout']);
        }

        if (array_key_exists('images', $validated)) {
            $data['images'] = $this->imagesFrom((array) $validated['images']);
        }

        if (isset($validated['signature_layout'])) {
            $data['signature_layout'] = $this->signatureLayoutFrom($validated['signature_layout']);
        }

        return $data;
    }

    /**
     * Daftar gambar "Gambar & Dimensi". Entri boleh berupa gambar yang sudah
     * tersimpan ('path') atau unggahan baru berupa data URL ('data'). Gambar
     * yang tidak lagi dikirim berarti dihapus dari form.
     *
     * @param  array<int, mixed>  $rows
     * @return list<array{path: string, x: float, y: float, w: float}>
     */
    private function imagesFrom(array $rows): array
    {
        $images = [];

        foreach (array_values($rows) as $i => $row) {
            $row = (array) $row;
            $path = trim((string) ($row['path'] ?? ''));

            if ($path === '' && ($row['data'] ?? '') !== '') {
                $path = $this->storeDataUrl((string) $row['data']) ?? '';
            }

            if ($path === '') {
                continue;
            }

            $default = FabricationRequest::defaultImageBox(count($images));

            $images[] = [
                'path' => $path,
                'x' => round((float) ($row['x'] ?? $default['x']), 2),
                'y' => round((float) ($row['y'] ?? $default['y']), 2),
                'w' => round((float) ($row['w'] ?? $default['w']), 2),
            ];
        }

        return $images;
    }

    /**
     * Simpan gambar yang dikirim sebagai data URL ke storage publik.
     * Mengembalikan path relatif, atau null bila datanya tidak valid.
     */
    private function storeDataUrl(string $dataUrl): ?string
    {
        if (!preg_match('#^data:image/(jpeg|jpg|png|gif|webp);base64,#i', $dataUrl, $m)) {
            return null;
        }

        $binary = base64_decode(substr($dataUrl, strlen($m[0])), true);

        // Batas 5 MB, sama dengan aturan unggahan berkas biasa.
        if ($binary === false || $binary === '' || strlen($binary) > 5 * 1024 * 1024) {
            return null;
        }

        $ext = strtolower($m[1] === 'jpeg' ? 'jpg' : $m[1]);
        $relative = 'fr-sketches/' . Str::random(40) . '.' . $ext;

        Storage::disk('public')->put($relative, $binary);

        return 'storage/' . $relative;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, array{x: float, y: float, w: float}>
     */
    private function signatureLayoutFrom(array $input): array
    {
        $layout = [];

        foreach (array_keys(FabricationRequest::SIGNATURE_ROLES) as $role) {
            $box = (array) ($input[$role] ?? []);

            if (!isset($box['x'], $box['y'], $box['w'])) {
                continue;
            }

            $layout[$role] = [
                'x' => round((float) $box['x'], 2),
                'y' => round((float) $box['y'], 2),
                'w' => round((float) $box['w'], 2),
            ];
        }

        return $layout;
    }

    /**
     * Bersihkan posisi & ukuran gambar hasil geser/resize di form.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, array{x: float, y: float, w: float}>
     */
    private function imageLayoutFrom(array $input): array
    {
        $layout = [];

        foreach (['image', 'image_2'] as $slot) {
            $box = (array) ($input[$slot] ?? []);

            if (!isset($box['x'], $box['y'], $box['w'])) {
                continue;
            }

            $layout[$slot] = [
                'x' => round((float) $box['x'], 2),
                'y' => round((float) $box['y'], 2),
                'w' => round((float) $box['w'], 2),
            ];
        }

        return $layout;
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

    /**
     * Gabungkan data tanda tangan lama dengan kiriman baru. Gambar yang tidak
     * diunggah ulang tetap dipertahankan supaya tidak hilang saat mengedit
     * kolom lain.
     *
     * @return array<string, array<string, mixed>>
     */
    private function signaturesFrom(Request $request, ?FabricationRequest $fr): array
    {
        $input = (array) $request->input('signatures', []);
        $result = [];

        foreach (array_keys(FabricationRequest::SIGNATURE_ROLES) as $role) {
            $existing = $fr ? $fr->signature($role) : ['name' => '', 'date' => null, 'image' => null];
            $row = (array) ($input[$role] ?? []);

            $image = $existing['image'];
            if ($file = $request->file("signatures.{$role}.image")) {
                $image = 'storage/' . $file->store('fr-signatures', 'public');
            } elseif (!empty($row['remove_image'])) {
                // Dihapus lewat klik kanan pada gambar tanda tangan
                $image = null;
            } elseif (!empty($row['remove_image'])) {
                $image = null;   // dihapus lewat klik kanan di form
            }

            $entry = [
                'name' => trim((string) ($row['name'] ?? $existing['name'])),
                'date' => ($row['date'] ?? $existing['date']) ?: null,
                'image' => $image,
            ];

            // Simpan hanya kolom yang benar-benar terisi agar JSON tetap ringkas.
            if ($entry['name'] !== '' || $entry['date'] || $entry['image']) {
                $result[$role] = $entry;
            }
        }

        return $result;
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

        if (auth()->check() && auth()->user()->hasAnyRole(['Mechanic', 'Supervisor', 'SuperAdmin'])) {
            if ($fr->status === 'draft') {
                $fr->update(['status' => 'printed']);
            }
        }

        // Form PLO/09/F-021 asli dicetak A4 landscape.
        $pdf = Pdf::loadView('fr.pdf', [
            'component' => $component,
            'fr' => $fr,
        ]);
        $pdf->setPaper('a4', 'landscape');

        $filename = str_replace(['/', '\\'], '_', $fr->fr_number) . '.pdf';

        return $pdf->stream($filename);
    }
}
