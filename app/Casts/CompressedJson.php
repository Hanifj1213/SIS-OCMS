<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Simpan array besar sebagai JSON yang di-gzip lalu di-base64.
 *
 * Layout satu template bisa 700 KB dalam bentuk JSON biasa; untuk 44 template
 * totalnya 30 MB. Karena database/database.sqlite ikut di-commit ke git,
 * ukuran itu membuat repo membengkak setiap kali diimpor ulang. Dikompresi,
 * totalnya tinggal ~4,5 MB.
 *
 * Base64 dipakai supaya isinya tetap aman di kolom teks pada semua driver.
 */
class CompressedJson implements CastsAttributes
{
    /** Penanda agar data lama (JSON polos) tetap terbaca. */
    private const PREFIX = 'gz:';

    public function get(Model $model, string $key, mixed $value, array $attributes): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value) && str_starts_with($value, self::PREFIX)) {
            $binary = base64_decode(substr($value, strlen(self::PREFIX)), true);
            if ($binary === false) {
                return null;
            }

            $json = @gzdecode($binary);
            if ($json === false) {
                return null;
            }

            return json_decode($json, true);
        }

        // Data lama sebelum kompresi diterapkan.
        return json_decode((string) $value, true);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        $json = is_string($value) ? $value : json_encode($value);
        if ($json === false) {
            return null;
        }

        return self::PREFIX . base64_encode(gzencode($json, 6));
    }
}
