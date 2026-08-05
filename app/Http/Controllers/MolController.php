<?php

namespace App\Http\Controllers;

use App\Models\Component;
use App\Services\MolExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Mechanic Order List (MOL) — form pemesanan part gudang.
 *
 * Layout mengikuti template `MOL.xlsx` tab "ADD 1": header WO/unit,
 * legenda reason code A–K, lalu grid baris part (NO, FIGURE, INDEX,
 * PART NUMBER, DESCRIPTION, QTY, COMP GROUP, REASON CODE, REMARK).
 * Baris MOL disimpan sebagai Part Request milik komponen.
 */
class MolController extends Controller
{
    private function authorizeMechanic(): void
    {
        if (!auth()->user()->hasAnyRole(['Mechanic', 'Supervisor', 'SuperAdmin'])) {
            abort(403, 'Hanya Mechanic/Supervisor/SuperAdmin yang boleh menerbitkan MOL.');
        }
    }

    /**
     * Form MOL kosong. Baris part yang sudah ada ditampilkan lebih dulu,
     * lalu disediakan baris kosong tambahan untuk pengisian baru.
     */
    public function create(Component $component)
    {
        $this->authorizeMechanic();

        $component->load('partRequests');

        // 'comp', bukan 'component': di dalam <x-app-layout> Blade menimpa
        // $component dengan instance komponen layout-nya.
        return view('mol.form', [
            'comp' => $component,
            'existing' => $component->partRequests,
            'orderCodes' => MolExportService::ORDER_CODES,
            'blankRows' => 10,
        ]);
    }

    /**
     * Simpan header MOL di komponen + baris part sebagai Part Request.
     * Baris tanpa nama part diabaikan agar form bisa dikirim setengah terisi.
     */
    public function store(Request $request, Component $component)
    {
        $this->authorizeMechanic();

        $validated = $request->validate([
            'mol_wo_number' => 'nullable|string|max:255',
            'mol_order_type' => 'nullable|string|max:255',
            'mol_order_date' => 'nullable|date',
            'mol_ir_number' => 'nullable|string|max:255',
            'mol_ir_date' => 'nullable|date',
            'mol_note' => 'nullable|string|max:2000',
            'rows' => 'nullable|array',
            'rows.*.part_name' => 'nullable|string|max:255',
            'rows.*.part_number' => 'nullable|string|max:255',
            'rows.*.figure' => 'nullable|string|max:255',
            'rows.*.index_no' => 'nullable|string|max:255',
            'rows.*.section' => 'nullable|string|max:255',
            'rows.*.qty' => 'nullable|integer|min:1',
            'rows.*.order_code' => 'nullable|in:' . implode(',', array_keys(MolExportService::ORDER_CODES)),
            'rows.*.remarks' => 'nullable|string|max:1000',
        ]);

        $component->update([
            'mol_wo_number' => $validated['mol_wo_number'] ?? null,
            'mol_order_type' => $validated['mol_order_type'] ?? null,
            'mol_order_date' => $validated['mol_order_date'] ?? null,
            'mol_ir_number' => $validated['mol_ir_number'] ?? null,
            'mol_ir_date' => $validated['mol_ir_date'] ?? null,
            'mol_note' => $validated['mol_note'] ?? null,
        ]);

        $created = 0;

        foreach ($validated['rows'] ?? [] as $row) {
            $partName = trim((string) ($row['part_name'] ?? ''));
            if ($partName === '') {
                continue;
            }

            $component->partRequests()->create([
                'wo_number' => $validated['mol_wo_number'] ?? null,
                'part_name' => $partName,
                'part_number' => trim((string) ($row['part_number'] ?? '')) ?: null,
                'figure' => trim((string) ($row['figure'] ?? '')) ?: null,
                'index_no' => trim((string) ($row['index_no'] ?? '')) ?: null,
                'section' => trim((string) ($row['section'] ?? '')) ?: null,
                'qty' => max(1, (int) ($row['qty'] ?? 1)),
                'order_code' => $row['order_code'] ?? 'A',
                'remarks' => trim((string) ($row['remarks'] ?? '')) ?: null,
                'status' => 'Pending',
            ]);

            $created++;
        }

        $message = $created > 0
            ? "MOL disimpan, {$created} baris part ditambahkan."
            : 'Header MOL disimpan. Belum ada baris part baru yang diisi.';

        return redirect()->route('components.mol.create', $component->comp_id)
            ->with('success', $message);
    }

    /**
     * Upload dokumen MOL (PDF atau foto scan) untuk komponen.
     */
    public function uploadDocument(Request $request, Component $component)
    {
        $this->authorizeMechanic();

        $request->validate([
            'mol_document' => 'required|file|mimes:pdf,jpeg,jpg,png|max:10240',
        ]);

        // Hapus file lama jika ada
        if ($component->mol_document_path) {
            $relative = str_replace('storage/', '', $component->mol_document_path);
            Storage::disk('public')->delete($relative);
        }

        $file = $request->file('mol_document');
        $path = 'storage/' . $file->store('mol-documents', 'public');

        $component->update(['mol_document_path' => $path]);

        if ($request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Dokumen MOL berhasil diupload.',
                'path' => asset($path),
                'filename' => basename($path),
            ]);
        }

        return redirect()->back()->with('success', 'Dokumen MOL berhasil diupload.');
    }

    /**
     * Hapus dokumen MOL komponen.
     */
    public function deleteDocument(Request $request, Component $component)
    {
        $this->authorizeMechanic();

        if ($component->mol_document_path) {
            $relative = str_replace('storage/', '', $component->mol_document_path);
            Storage::disk('public')->delete($relative);
            $component->update(['mol_document_path' => null]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Dokumen MOL berhasil dihapus.',
            ]);
        }

        return redirect()->back()->with('success', 'Dokumen MOL berhasil dihapus.');
    }
}
