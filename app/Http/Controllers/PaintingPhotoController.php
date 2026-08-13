<?php

namespace App\Http\Controllers;

use App\Models\Component;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Stage 5 (Test Performance & Painting): dokumentasi foto hasil pengecatan.
 * Dipisah dari ComponentController agar controller utama tetap ramping.
 */
class PaintingPhotoController extends Controller
{
    public function upload(Request $request, Component $component)
    {
        if (! auth()->user()->canOperateOverhaul()) {
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

    public function destroy(Request $request, Component $component)
    {
        if (! auth()->user()->canOperateOverhaul()) {
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
            Storage::disk('public')->delete($relative);
        }

        return redirect()
            ->to(route('components.show', $component->comp_id) . '#painting-panel')
            ->with('success', 'Foto painting dihapus.');
    }
}
