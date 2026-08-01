<?php

namespace App\Services;

use App\Models\FabricationRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Resolver kepemilikan & keamanan path lampiran FR (sketsa, tanda tangan).
 * Mencegah path traversal, URL, path absolut, dan reuse file FR lain.
 */
class FrAttachmentResolver
{
    /** @var list<string> */
    public const ALLOWED_DIRS = ['fr-sketches', 'fr-signatures'];

    /** @var list<string> */
    public const ALLOWED_MIMES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    /**
     * Normalisasi path klien ke bentuk `storage/{dir}/{filename}` atau null bila tidak valid.
     */
    public function normalizeClientPath(string $path): ?string
    {
        $path = trim(str_replace('\\', '/', $path));

        if ($path === '' || str_contains($path, "\0")) {
            return null;
        }

        if (preg_match('#^(https?://|file://|ftp://|//)#i', $path)) {
            return null;
        }

        if (preg_match('#^[a-zA-Z]:/#', $path) || str_starts_with($path, '/')) {
            return null;
        }

        if (str_contains($path, '..')) {
            return null;
        }

        if (! preg_match('#^storage/(fr-sketches|fr-signatures)/[A-Za-z0-9._-]+$#', $path, $matches)) {
            return null;
        }

        if (! in_array($matches[1], self::ALLOWED_DIRS, true)) {
            return null;
        }

        $relative = substr($path, strlen('storage/'));

        if (! Storage::disk('public')->exists($relative)) {
            return null;
        }

        if (! $this->mimeIsAllowed(Storage::disk('public')->path($relative))) {
            return null;
        }

        return 'storage/'.$relative;
    }

    /**
     * @return list<string>
     */
    public function collectOwnedPaths(FabricationRequest $fr): array
    {
        $paths = [];

        foreach ($fr->imageList() as $img) {
            $normalized = $this->normalizeClientPath((string) ($img['path'] ?? ''));
            if ($normalized !== null) {
                $paths[] = $normalized;
            }
        }

        foreach (['image_path', 'image_path_2'] as $column) {
            $normalized = $this->normalizeClientPath((string) ($fr->{$column} ?? ''));
            if ($normalized !== null) {
                $paths[] = $normalized;
            }
        }

        foreach (array_keys(FabricationRequest::SIGNATURE_ROLES) as $role) {
            $normalized = $this->normalizeClientPath((string) ($fr->signature($role)['image'] ?? ''));
            if ($normalized !== null) {
                $paths[] = $normalized;
            }
        }

        return array_values(array_unique($paths));
    }

    /**
     * Path existing dari request hanya boleh milik FR yang sedang diedit.
     */
    public function assertExistingPathAllowed(?FabricationRequest $fr, string $path): string
    {
        $normalized = $this->normalizeClientPath($path);

        if ($normalized === null) {
            throw ValidationException::withMessages([
                'images' => 'Path lampiran tidak valid atau file tidak ditemukan.',
            ]);
        }

        if ($fr === null) {
            throw ValidationException::withMessages([
                'images' => 'Path lampiran existing tidak diizinkan saat membuat FR baru.',
            ]);
        }

        if (! in_array($normalized, $this->collectOwnedPaths($fr), true)) {
            throw ValidationException::withMessages([
                'images' => 'Path lampiran bukan milik Fabrication Request ini.',
            ]);
        }

        return $normalized;
    }

    /**
     * Gambar siap PDF — hanya path milik FR yang lolos resolver.
     *
     * @return list<array{path: string, file: string, x: float, y: float, w: float}>
     */
    public function resolveImagesForPdf(FabricationRequest $fr): array
    {
        $owned = $this->collectOwnedPaths($fr);
        $resolved = [];

        foreach ($fr->imageList() as $img) {
            $normalized = $this->normalizeClientPath((string) ($img['path'] ?? ''));
            if ($normalized === null || ! in_array($normalized, $owned, true)) {
                continue;
            }

            $relative = substr($normalized, strlen('storage/'));
            $absolute = Storage::disk('public')->path($relative);

            $resolved[] = [
                'path' => $normalized,
                'file' => $absolute,
                'x' => (float) ($img['x'] ?? 0),
                'y' => (float) ($img['y'] ?? 0),
                'w' => (float) ($img['w'] ?? 0),
            ];
        }

        return $resolved;
    }

    public function resolveSignatureImageForPdf(FabricationRequest $fr, string $role): ?string
    {
        $path = (string) ($fr->signature($role)['image'] ?? '');
        $normalized = $this->normalizeClientPath($path);

        if ($normalized === null || ! in_array($normalized, $this->collectOwnedPaths($fr), true)) {
            return null;
        }

        $relative = substr($normalized, strlen('storage/'));

        return Storage::disk('public')->path($relative);
    }

    public function mimeIsAllowed(string $absolutePath): bool
    {
        if (! is_file($absolutePath)) {
            return false;
        }

        $mime = mime_content_type($absolutePath);

        return is_string($mime) && in_array(strtolower($mime), self::ALLOWED_MIMES, true);
    }

    public function verifyBinaryImage(string $binary): bool
    {
        if ($binary === '') {
            return false;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->buffer($binary);

        return is_string($mime) && in_array(strtolower($mime), self::ALLOWED_MIMES, true);
    }
}
