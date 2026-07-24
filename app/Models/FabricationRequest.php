<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FabricationRequest extends Model
{
    use HasFactory;

    protected $primaryKey = 'fr_id';

    /** Jenis pekerjaan pada form PLO/09/F-021 (kotak centang, boleh lebih dari satu). */
    public const WORK_TYPES = ['repair', 'fabrikasi', 'modifikasi', 'others'];

    /** Nilai bawaan blok kode formulir, sesuai form cetak PLO/09/F-021. */
    public const FORM_DEFAULTS = [
        'form_no' => 'PLO/09/F-021',
        'sop_no' => 'PLO/09/000/SOP',
        'form_owner' => 'Plant Operation Dept.',
        'form_revision' => '1',
    ];

    /**
     * Nilai blok kode formulir; jatuh ke bawaan bila belum pernah diisi.
     */
    public function formCode(string $key): string
    {
        $value = trim((string) ($this->{$key} ?? ''));

        return $value !== '' ? $value : (self::FORM_DEFAULTS[$key] ?? '');
    }

    /** Kolom approval pada form, berurutan kiri ke kanan. */
    public const SIGNATURE_ROLES = [
        'received_by' => ['label' => 'Received by,', 'sub' => 'External Workshop,'],
        'sent_by' => ['label' => 'Sent by,', 'sub' => 'Warehouse Keeper,'],
        'approved_by' => ['label' => 'Approved by,', 'sub' => 'Plant Sect. Head,'],
        'checked_by' => ['label' => 'Checked by,', 'sub' => 'Group Leader'],
        'ordered_by' => ['label' => 'Ordered by,', 'sub' => 'Mechanic'],
    ];

    protected $fillable = [
        'comp_id',
        'fr_number',
        'form_no',
        'sop_no',
        'form_owner',
        'form_revision',
        'ro_number',
        'pr_number',
        'request_date',
        'estimation_date',
        'location_site',
        'unit_model',
        'component_model',
        'unit_code',
        'work_order_for',
        'sent_to',
        'address',
        'attn',
        'part_number',
        'part_name',
        'section',
        'qty',
        'brand',
        'unit_price',
        'labour_cost',
        'work_type',
        'work_types',
        'instruction',
        'image_path',
        'image_path_2',
        'image_layout',
        'images',
        'annotations',
        'signature_layout',
        'note',
        'signatures',
        'source',
        'status',
        'completed_at',
        'completion_notes',
        'created_by',
    ];

    protected $casts = [
        'request_date' => 'date',
        'estimation_date' => 'date',
        'completed_at' => 'datetime',
        'unit_price' => 'decimal:2',
        'labour_cost' => 'decimal:2',
        'work_types' => 'array',
        'signatures' => 'array',
        'image_layout' => 'array',
        'images' => 'array',
        'annotations' => 'array',
        'signature_layout' => 'array',
    ];

    /** Komposisi bawaan gambar ke-n pada kolom "Gambar & Dimensi". */
    private const IMAGE_DEFAULTS = [
        ['x' => 2.0, 'y' => 3.0, 'w' => 46.0],
        ['x' => 50.0, 'y' => 3.0, 'w' => 46.0],
        ['x' => 2.0, 'y' => 40.0, 'w' => 46.0],
        ['x' => 50.0, 'y' => 40.0, 'w' => 46.0],
        ['x' => 26.0, 'y' => 70.0, 'w' => 46.0],
    ];

    /**
     * Daftar gambar pada kolom "Gambar & Dimensi", masing-masing dengan posisi
     * dan ukuran dalam persen. Kolom lama (image_path / image_path_2) ikut
     * dibaca supaya FR yang sudah tersimpan tetap tampil.
     *
     * @return list<array{path: string, x: float, y: float, w: float}>
     */
    public function imageList(): array
    {
        $rows = is_array($this->images) ? $this->images : [];

        // Data lama: pindahkan ke bentuk daftar saat dibaca.
        if ($rows === []) {
            foreach (['image_path' => 'image', 'image_path_2' => 'image_2'] as $column => $slot) {
                if ($this->{$column}) {
                    $rows[] = ['path' => $this->{$column}] + $this->imageBox($slot);
                }
            }
        }

        $list = [];

        foreach (array_values($rows) as $i => $row) {
            $path = trim((string) ($row['path'] ?? ''));
            if ($path === '') {
                continue;
            }

            $default = self::IMAGE_DEFAULTS[$i] ?? self::IMAGE_DEFAULTS[array_key_last(self::IMAGE_DEFAULTS)];

            $list[] = [
                'path' => $path,
                'x' => (float) ($row['x'] ?? $default['x']),
                'y' => (float) ($row['y'] ?? $default['y']),
                'w' => (float) ($row['w'] ?? $default['w']),
            ];
        }

        return $list;
    }

    /**
     * Daftar anotasi gambar (garis, panah, teks) jika ada.
     *
     * @return list<array>
     */
    public function annotationList(): array
    {
        $raw = $this->annotations ?? null;

        if (is_array($raw)) {
            return $this->normalizeAnnotationNumbers($raw);
        }

        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            return is_array($decoded) ? $this->normalizeAnnotationNumbers($decoded) : [];
        }

        return [];
    }

    /**
     * JSON tidak membedakan 3 dan 3.0 saat dibaca kembali dari SQLite.
     * Normalisasi ini menjaga koordinat/properti anotasi tetap konsisten untuk
     * renderer dan pemanggil API, sekaligus tidak mengubah format JSON mentah.
     *
     * @param  list<array<string, mixed>>  $annotations
     * @return list<array<string, mixed>>
     */
    private function normalizeAnnotationNumbers(array $annotations): array
    {
        foreach ($annotations as &$annotation) {
            if (!is_array($annotation)) {
                continue;
            }

            foreach (['x', 'y', 'x1', 'y1', 'x2', 'y2', 'stroke', 'font_size', 'size'] as $key) {
                if (array_key_exists($key, $annotation) && is_numeric($annotation[$key])) {
                    $annotation[$key] = (float) $annotation[$key];
                }
            }

            if (isset($annotation['points']) && is_array($annotation['points'])) {
                foreach ($annotation['points'] as &$point) {
                    if (!is_array($point)) {
                        continue;
                    }
                    foreach (['x', 'y'] as $key) {
                        if (array_key_exists($key, $point) && is_numeric($point[$key])) {
                            $point[$key] = (float) $point[$key];
                        }
                    }
                }
                unset($point);
            }
        }
        unset($annotation);

        return $annotations;
    }

    /** Komposisi bawaan untuk gambar baru ke-$index (0-based). */
    public static function defaultImageBox(int $index): array
    {
        return self::IMAGE_DEFAULTS[$index] ?? self::IMAGE_DEFAULTS[array_key_last(self::IMAGE_DEFAULTS)];
    }

    /**
     * Posisi & ukuran gambar tanda tangan satu kolom approval (persen).
     *
     * @return array{x: float, y: float, w: float}
     */
    public function signatureBox(string $role): array
    {
        $box = $this->signature_layout[$role] ?? [];

        return [
            'x' => (float) ($box['x'] ?? 12.0),
            'y' => (float) ($box['y'] ?? 10.0),
            'w' => (float) ($box['w'] ?? 74.0),
        ];
    }

    /**
     * Identitas unit pada form. Nilai yang disunting pada FR menang; bila
     * kosong, dipakai data komponen supaya form tetap terisi otomatis
     * seperti hasil scan.
     */
    public function identity(string $field, ?Component $component = null): string
    {
        $own = trim((string) ($this->{$field} ?? ''));
        if ($own !== '') {
            return $own;
        }

        $component ??= $this->component;
        if (!$component) {
            return '';
        }

        return match ($field) {
            'unit_model' => (string) ($component->model_type ?: $component->egi),
            'component_model' => (string) ($component->component_model ?: $component->major_category),
            'unit_code' => (string) ($component->unit_code ?: $component->serial_number),
            default => '',
        };
    }

    /**
     * Posisi & ukuran satu gambar pada kolom "Gambar & Dimensi", dalam persen
     * terhadap kotak gambar supaya tetap proporsional di layar maupun cetak.
     *
     * @return array{x: float, y: float, w: float}
     */
    public function imageBox(string $slot): array
    {
        $box = $this->image_layout[$slot] ?? [];

        // Bawaan: gambar 1 di kiri, gambar 2 di kanan sedikit lebih besar,
        // meniru komposisi pada form asli.
        $defaults = $slot === 'image_2'
            ? ['x' => 46.0, 'y' => 6.0, 'w' => 52.0]
            : ['x' => 2.0, 'y' => 4.0, 'w' => 40.0];

        return [
            'x' => (float) ($box['x'] ?? $defaults['x']),
            'y' => (float) ($box['y'] ?? $defaults['y']),
            'w' => (float) ($box['w'] ?? $defaults['w']),
        ];
    }

    /**
     * Jenis pekerjaan yang tercentang pada form. Form asli membolehkan lebih
     * dari satu (mis. Repair + Fabrikasi); data lama hanya punya work_type
     * tunggal, jadi dipakai sebagai fallback.
     *
     * @return list<string>
     */
    public function workTypes(): array
    {
        $types = $this->work_types;

        if (is_array($types) && $types !== []) {
            return array_values(array_intersect(self::WORK_TYPES, $types));
        }

        return $this->work_type ? [$this->work_type] : [];
    }

    public function hasWorkType(string $type): bool
    {
        return in_array($type, $this->workTypes(), true);
    }

    /**
     * Data tanda tangan satu kolom approval.
     *
     * @return array{name: string, date: ?string, image: ?string}
     */
    public function signature(string $role): array
    {
        $data = $this->signatures[$role] ?? [];

        return [
            'name' => (string) ($data['name'] ?? ''),
            'date' => $data['date'] ?? null,
            'image' => $data['image'] ?? null,
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'fr_id';
    }

    public function component()
    {
        return $this->belongsTo(Component::class, 'comp_id', 'comp_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function workTypeLabel(): string
    {
        return match ($this->work_type) {
            'fabrikasi' => 'Fabrikasi',
            'modifikasi' => 'Modifikasi',
            default => 'Repair',
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'printed' => 'Printed',
            'done' => 'Done',
            default => 'Draft',
        };
    }
}
