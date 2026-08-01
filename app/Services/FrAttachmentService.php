<?php

namespace App\Services;

use App\Models\FabricationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Penanganan lampiran form FR PLO/09/F-021: gambar/sketsa "Gambar & Dimensi"
 * (unggahan file maupun data URL base64) dan tanda tangan per role.
 * Dipisah dari FabricationRequestController agar controller fokus ke alur HTTP.
 */
class FrAttachmentService
{
    public function __construct(
        private readonly FrAttachmentResolver $resolver,
    ) {}

    /**
     * Daftar gambar "Gambar & Dimensi". Entri boleh berupa gambar yang sudah
     * tersimpan ('path') atau unggahan baru berupa data URL ('data'). Gambar
     * yang tidak lagi dikirim berarti dihapus dari form.
     *
     * Path existing wajib milik FR yang sedang diedit (ownership resolver).
     *
     * @param  array<int, mixed>  $rows
     * @return list<array{path: string, x: float, y: float, w: float}>
     */
    public function imagesFrom(array $rows, ?FabricationRequest $fr = null): array
    {
        $images = [];

        foreach (array_values($rows) as $i => $row) {
            $row = (array) $row;
            $path = trim((string) ($row['path'] ?? ''));

            if ($path === '' && ($row['data'] ?? '') !== '') {
                $path = $this->storeDataUrl((string) $row['data']) ?? '';
            } elseif ($path !== '') {
                $path = $this->resolver->assertExistingPathAllowed($fr, $path);
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
     * Bersihkan objek anotasi sebelum disimpan sebagai JSON. Jenis dan warna
     * dibatasi daftar putih; koordinat tetap berupa persen terhadap kanvas.
     *
     * @param  array<int, mixed>  $rows
     * @return list<array<string, float|string>>
     */
    public function annotationsFrom(array $rows): array
    {
        $annotations = [];

        foreach (array_values($rows) as $row) {
            $row = (array) $row;
            $rawType = (string) ($row['type'] ?? '');
            $type = str_replace('_', '-', $rawType);
            // Canonical storage mengikuti format lama untuk kompatibilitas
            // dokumen/test yang sudah menyimpan `double_arrow`.
            $storedType = $type === 'double-arrow' ? 'double_arrow' : $type;
            $color = $this->annotationColor((string) ($row['color'] ?? ''));

            if (in_array($type, ['line', 'arrow', 'double-arrow', 'connector'], true)) {
                if (! isset($row['x1'], $row['y1'], $row['x2'], $row['y2'])) {
                    continue;
                }

                $annotation = [
                    'type' => $storedType,
                    'x1' => round((float) $row['x1'], 2),
                    'y1' => round((float) $row['y1'], 2),
                    'x2' => round((float) $row['x2'], 2),
                    'y2' => round((float) $row['y2'], 2),
                    'color' => $color,
                    'stroke' => round((float) ($row['stroke'] ?? 2), 2),
                ];
                if ($type === 'connector') {
                    $annotation['endpoints'] = true;
                }
                $annotations[] = $annotation;

                continue;
            }

            if ($type !== 'text') {
                continue;
            }

            $text = trim((string) ($row['text'] ?? ''));
            if ($text === '' || ! isset($row['x'], $row['y'])) {
                continue;
            }

            $annotations[] = [
                'type' => 'text',
                'x' => round((float) $row['x'], 2),
                'y' => round((float) $row['y'], 2),
                'text' => $text,
                'color' => $color,
                'font_size' => round((float) ($row['font_size'] ?? 5), 2),
            ];
        }

        return $annotations;
    }

    /**
     * Simpan gambar yang dikirim sebagai data URL ke storage publik.
     * Mengembalikan path relatif, atau null bila datanya tidak valid.
     */
    public function storeDataUrl(string $dataUrl): ?string
    {
        if (! preg_match('#^data:image/(jpeg|jpg|png|gif|webp);base64,#i', $dataUrl, $m)) {
            return null;
        }

        $binary = base64_decode(substr($dataUrl, strlen($m[0])), true);

        // Batas 5 MB, sama dengan aturan unggahan berkas biasa.
        if ($binary === false || $binary === '' || strlen($binary) > 5 * 1024 * 1024) {
            return null;
        }

        if (! $this->resolver->verifyBinaryImage($binary)) {
            return null;
        }

        $ext = strtolower($m[1] === 'jpeg' ? 'jpg' : $m[1]);
        $relative = 'fr-sketches/'.Str::random(40).'.'.$ext;

        Storage::disk('public')->put($relative, $binary);

        return 'storage/'.$relative;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, array{x: float, y: float, w: float}>
     */
    public function signatureLayoutFrom(array $input): array
    {
        $layout = [];

        foreach (array_keys(FabricationRequest::SIGNATURE_ROLES) as $role) {
            $box = (array) ($input[$role] ?? []);

            if (! isset($box['x'], $box['y'], $box['w'])) {
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
    public function imageLayoutFrom(array $input): array
    {
        $layout = [];

        foreach (['image', 'image_2'] as $slot) {
            $box = (array) ($input[$slot] ?? []);

            if (! isset($box['x'], $box['y'], $box['w'])) {
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
     * Gabungkan data tanda tangan lama dengan kiriman baru. Gambar yang tidak
     * diunggah ulang tetap dipertahankan supaya tidak hilang saat mengedit
     * kolom lain.
     *
     * @return array<string, array<string, mixed>>
     */
    public function signaturesFrom(Request $request, ?FabricationRequest $fr): array
    {
        $input = (array) $request->input('signatures', []);
        $result = [];

        foreach (array_keys(FabricationRequest::SIGNATURE_ROLES) as $role) {
            $existing = $fr ? $fr->signature($role) : ['name' => '', 'date' => null, 'image' => null];
            $row = (array) ($input[$role] ?? []);

            $image = $existing['image'];
            if ($file = $request->file("signatures.{$role}.image")) {
                $stored = $file->store('fr-signatures', 'public');
                $candidate = 'storage/'.$stored;
                if ($this->resolver->normalizeClientPath($candidate) === null) {
                    Storage::disk('public')->delete($stored);
                    $image = $existing['image'];
                } else {
                    $image = $candidate;
                }
            } elseif (! empty($row['remove_image'])) {
                // Dihapus lewat klik kanan pada gambar tanda tangan
                $image = null;
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

    private function annotationColor(string $color): string
    {
        return preg_match('/^#[0-9a-f]{6}$/i', $color) ? strtolower($color) : '#ef4444';
    }
}
