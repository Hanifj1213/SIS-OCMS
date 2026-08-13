<?php

namespace App\Http\Controllers;

use App\Models\Component;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Stage 4 (Assembly): unggah dokumen (PDF) / foto dokumentasi perakitan.
 * Dipisah dari ComponentController agar controller utama tetap ramping.
 */
class AssemblyDocumentController extends Controller
{
    public function upload(Request $request, Component $component)
    {
        if (! auth()->user()->canOperateOverhaul()) {
            return back()->withErrors(['assembly' => 'Anda tidak memiliki izin mengunggah dokumen assembly.']);
        }

        $request->validate([
            'documents' => 'required|array|min:1|max:12',
            'documents.*' => 'file|mimes:pdf,jpeg,png,jpg,webp|max:10240',
        ]);

        $documents = $component->assembly_documents ?? [];

        foreach ($request->file('documents') as $doc) {
            $documents[] = [
                'path' => 'storage/' . $doc->store('assembly-documents', 'public'),
                'name' => $doc->getClientOriginalName(),
                'type' => strtolower($doc->getClientOriginalExtension()) === 'pdf' ? 'pdf' : 'image',
                'uploaded_at' => now()->toDateTimeString(),
                'uploaded_by' => auth()->user()->name,
            ];
        }

        $component->update(['assembly_documents' => $documents]);

        return redirect()
            ->to(route('components.show', $component->comp_id) . '#assembly-documents-panel')
            ->with('success', count($request->file('documents')) . ' dokumen assembly berhasil diunggah.');
    }

    public function destroy(Request $request, Component $component)
    {
        if (! auth()->user()->canOperateOverhaul()) {
            return back()->withErrors(['assembly' => 'Anda tidak memiliki izin menghapus dokumen assembly.']);
        }

        $request->validate(['index' => 'required|integer|min:0']);

        $documents = array_values($component->assembly_documents ?? []);
        $index = $request->integer('index');

        if (!array_key_exists($index, $documents)) {
            return back()->withErrors(['assembly' => 'Dokumen tidak ditemukan.']);
        }

        $removed = $documents[$index];
        unset($documents[$index]);
        $component->update(['assembly_documents' => array_values($documents)]);

        $relative = preg_replace('#^storage/#', '', (string) ($removed['path'] ?? ''));
        if ($relative) {
            Storage::disk('public')->delete($relative);
        }

        return redirect()
            ->to(route('components.show', $component->comp_id) . '#assembly-documents-panel')
            ->with('success', 'Dokumen assembly berhasil dihapus.');
    }
}
