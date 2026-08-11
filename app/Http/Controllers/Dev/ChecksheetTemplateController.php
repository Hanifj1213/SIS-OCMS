<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\ComponentController;
use App\Http\Controllers\Controller;
use App\Models\ChecksheetTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

/**
 * Builder template checksheet internal — hanya Receiving (step 1) dan
 * Delivery (step 6). Step lain (2-5) memakai Google Sheets, bukan
 * checksheet database. Menggantikan edit manual lewat seeder.
 */
class ChecksheetTemplateController extends Controller
{
    public function index()
    {
        $templates = ChecksheetTemplate::query()
            ->orderBy('major_category')
            ->orderBy('egi_model')
            ->orderBy('stage_number')
            ->get();

        return view('dev.checksheet-templates', [
            'templates' => $templates,
            'stageNames' => ComponentController::STAGE_NAMES,
        ]);
    }

    /**
     * Buat template baru — kosong atau duplikat item dari template lain.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'major_category' => 'required|string|max:100',
            'egi_model' => 'nullable|string|max:100',
            'stage_number' => 'required|integer|in:1,6',
            'template_name' => 'required|string|max:255',
            'copy_from' => 'nullable|integer|exists:checksheet_templates,id',
        ]);

        $egi = filled($data['egi_model']) ? strtoupper(trim($data['egi_model'])) : null;

        $exists = ChecksheetTemplate::query()
            ->where('major_category', $data['major_category'])
            ->where('stage_number', $data['stage_number'])
            ->when(
                $egi === null,
                fn ($q) => $q->whereNull('egi_model'),
                fn ($q) => $q->whereRaw('UPPER(egi_model) = ?', [$egi]),
            )
            ->exists();

        if ($exists) {
            return back()->withInput()->withErrors([
                'template' => 'Template untuk kombinasi kategori/EGI/stage tersebut sudah ada. Edit template yang ada.',
            ]);
        }

        $items = [];
        if (! empty($data['copy_from'])) {
            $source = ChecksheetTemplate::find($data['copy_from']);
            $items = $source?->items ?? [];
        }

        $template = ChecksheetTemplate::create([
            'major_category' => trim($data['major_category']),
            'egi_model' => $egi,
            'stage_number' => (int) $data['stage_number'],
            'template_name' => trim($data['template_name']),
            'items' => $items,
        ]);

        return redirect()->route('dev.checksheet-templates.edit', $template)
            ->with('success', 'Template "' . $template->template_name . '" dibuat' . ($items !== [] ? ' dengan ' . count($items) . ' item hasil duplikat.' : '. Silakan tambahkan item.'));
    }

    public function edit(ChecksheetTemplate $checksheetTemplate)
    {
        $groups = collect($checksheetTemplate->items ?? [])
            ->pluck('group')->filter()->unique()->values()->all();

        // Galeri gambar referensi: hanya EGI milik template ini.
        // Template Generic (EGI kosong) berlaku semua EGI → tampilkan semua folder.
        $egiDirs = $checksheetTemplate->egi_model
            ? array_filter([$this->egiSlug($checksheetTemplate->egi_model)])
            : array_map('basename', glob(public_path('images/inspection/*'), GLOB_ONLYDIR) ?: []);

        $refImages = [];
        foreach ($egiDirs as $egiDir) {
            $targets = [null, ...$groups]; // null = gambar "semua item"
            foreach ($targets as $group) {
                $file = $this->refImageFile($checksheetTemplate, $group);
                $path = public_path("images/inspection/{$egiDir}/{$file}.png");
                if (is_file($path)) {
                    $refImages[] = [
                        'egi' => $egiDir,
                        'group' => $group,
                        'url' => asset("images/inspection/{$egiDir}/{$file}.png") . '?v=' . filemtime($path),
                    ];
                }
            }
        }

        return view('dev.checksheet-template-edit', [
            'template' => $checksheetTemplate,
            'stageNames' => ComponentController::STAGE_NAMES,
            'refGroups' => $groups,
            'refImages' => $refImages,
        ]);
    }

    /**
     * Upload gambar referensi checksheet.
     * Target "semua item" → {kategori}.png (satu gambar untuk seluruh checksheet).
     * Target grup tertentu → {kategori}--{grup}.png (Engine lama: {grup}.png).
     * Nama file harus sama persis dengan yang dicari slide view checksheet.
     */
    public function uploadImage(Request $request, ChecksheetTemplate $checksheetTemplate)
    {
        $data = $request->validate([
            'egi' => 'required|string|max:50',
            'group' => 'nullable|string|max:150',
            'image' => 'required|file|mimes:png,jpg,jpeg,webp|max:5120',
        ]);

        $egiSlug = $this->egiSlug($data['egi']);
        if ($egiSlug === '') {
            return back()->withErrors(['image' => 'EGI tidak valid.']);
        }

        $group = filled($data['group'] ?? null) ? trim($data['group']) : null;
        $file = $this->refImageFile($checksheetTemplate, $group);

        $dir = public_path('images/inspection/' . $egiSlug);
        File::ensureDirectoryExists($dir);
        // Selalu disimpan sebagai .png karena JS mencari nama itu.
        // Isi JPG/WebP tetap aman — browser mendeteksi format dari isi file.
        $request->file('image')->move($dir, $file . '.png');

        $scope = $group === null ? 'semua item' : 'grup "' . $group . '"';

        return back()->with('success', "Gambar referensi {$scope} tersimpan ({$egiSlug}/{$file}.png) — langsung tampil di checksheet komponen EGI tersebut.");
    }

    public function deleteImage(Request $request, ChecksheetTemplate $checksheetTemplate)
    {
        $data = $request->validate([
            'egi' => 'required|string|max:50',
            'group' => 'nullable|string|max:150',
        ]);

        $egiSlug = $this->egiSlug($data['egi']);
        $group = filled($data['group'] ?? null) ? trim($data['group']) : null;
        $file = $this->refImageFile($checksheetTemplate, $group);
        $path = public_path("images/inspection/{$egiSlug}/{$file}.png");

        if ($egiSlug !== '' && is_file($path)) {
            @unlink($path);

            return back()->with('success', "Gambar images/inspection/{$egiSlug}/{$file}.png dihapus.");
        }

        return back()->withErrors(['image' => 'File gambar tidak ditemukan.']);
    }

    /** Nama folder EGI: lowercase, hanya huruf/angka/strip (mengikuti JS slide view). */
    private function egiSlug(string $egi): string
    {
        $slug = str_replace(' ', '-', strtolower(trim($egi)));

        return preg_replace('/[^a-z0-9\-_]/', '', $slug);
    }

    /**
     * Nama file gambar (tanpa .png) — harus identik dengan pencarian di
     * checksheet-interactive.blade.php. Grup null = gambar "semua item".
     */
    private function refImageFile(ChecksheetTemplate $template, ?string $group = null): string
    {
        $catSlug = str_replace(['/', ' '], '-', strtolower($template->major_category));
        $groupSlug = str_replace(' ', '-', strtolower(trim((string) $group)));

        if ($groupSlug === '') {
            return $catSlug;
        }

        // Engine memakai nama lama {grup}.png agar ratusan gambar existing tetap terpakai
        return $template->major_category === 'Engine' ? $groupSlug : $catSlug . '--' . $groupSlug;
    }

    public function update(Request $request, ChecksheetTemplate $checksheetTemplate)
    {
        $data = $request->validate([
            'template_name' => 'required|string|max:255',
            'items' => 'nullable|array',
            'items.*.id' => 'nullable|string|max:50',
            'items.*.group' => 'nullable|string|max:150',
            'items.*.label' => 'required|string|max:500',
        ]);

        $submitted = array_values($data['items'] ?? []);

        // ID baru mengikuti pola penomoran yang sudah ada di template:
        // prefix terbanyak + lanjut dari nomor tertinggi (CVL-032 → CVL-033).
        // Kalau template belum punya pola, fallback RCV (Receiving) / DLV (Delivery).
        $prefixCounts = [];
        $maxPerPrefix = [];
        foreach ($submitted as $item) {
            if (preg_match('/^([A-Z][A-Z0-9]*)-(\d+)$/', trim((string) ($item['id'] ?? '')), $m)) {
                $prefixCounts[$m[1]] = ($prefixCounts[$m[1]] ?? 0) + 1;
                $maxPerPrefix[$m[1]] = max($maxPerPrefix[$m[1]] ?? 0, (int) $m[2]);
            }
        }
        arsort($prefixCounts);
        $prefix = array_key_first($prefixCounts)
            ?? ($checksheetTemplate->stage_number === 6 ? 'DLV' : 'RCV');

        $usedIds = [];
        $items = [];
        $sequence = ($maxPerPrefix[$prefix] ?? 0) + 1;

        foreach ($submitted as $item) {
            $id = trim((string) ($item['id'] ?? ''));

            // ID kosong / bentrok → generate ulang berurutan agar unik.
            while ($id === '' || in_array($id, $usedIds, true)) {
                $candidate = sprintf('%s-%03d', $prefix, $sequence++);
                $id = in_array($candidate, $usedIds, true) ? '' : $candidate;
            }

            $usedIds[] = $id;
            $items[] = [
                'id' => $id,
                'group' => trim((string) ($item['group'] ?? '')) ?: 'Umum',
                'label' => trim($item['label']),
            ];
        }

        $checksheetTemplate->update([
            'template_name' => trim($data['template_name']),
            'items' => $items,
        ]);

        return redirect()->route('dev.checksheet-templates.edit', $checksheetTemplate)
            ->with('success', 'Template disimpan (' . count($items) . ' item). Berlaku untuk komponen yang didaftarkan setelah ini.');
    }

    public function destroy(ChecksheetTemplate $checksheetTemplate)
    {
        $name = $checksheetTemplate->template_name;
        $checksheetTemplate->delete();

        return redirect()->route('dev.checksheet-templates.index')
            ->with('success', 'Template "' . $name . '" dihapus. Checksheet komponen yang sudah ada tidak terpengaruh.');
    }
}
