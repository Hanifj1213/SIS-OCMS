<?php

namespace App\Models;

use App\Casts\CompressedJson;
use Illuminate\Database\Eloquent\Model;

class SpreadsheetLayout extends Model
{
    protected $primaryKey = 'layout_id';

    protected $fillable = [
        'major_category',
        'egi_model',
        'kind',
        'source_file',
        'layout',
        'decision_map',
        'sheet_count',
        'part_row_count',
        'imported_at',
    ];

    protected $casts = [
        // Layout dikompresi karena database/database.sqlite ikut di-commit;
        // 30 MB JSON polos jadi ~4,5 MB. decision_map dibiarkan polos supaya
        // tetap enak dibaca/di-query — ukurannya hanya ~0,1 MB.
        'layout' => CompressedJson::class,
        'decision_map' => 'array',
        'imported_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'layout_id';
    }

    public function answers()
    {
        return $this->hasMany(ComponentSpreadsheetAnswer::class, 'layout_id', 'layout_id');
    }

    /**
     * Layout untuk komponen: cocokkan EGI persis dulu, lalu fallback generik.
     */
    public static function forComponent(Component $component, string $kind): ?self
    {
        $egi = strtoupper(trim((string) $component->egi));

        return static::query()
            ->where('major_category', $component->major_category)
            ->where('kind', $kind)
            ->where(fn ($q) => $q->whereRaw('UPPER(egi_model) = ?', [$egi])->orWhereNull('egi_model'))
            ->orderByRaw('egi_model IS NULL')   // yang spesifik menang
            ->first();
    }

    /** @return list<array<string, mixed>> */
    public function sheets(): array
    {
        return $this->layout['sheets'] ?? [];
    }

    /** @return array<int, array<string, mixed>> */
    public function styles(): array
    {
        return $this->layout['styles'] ?? [];
    }
}
