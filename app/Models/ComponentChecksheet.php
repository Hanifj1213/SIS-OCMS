<?php

namespace App\Models;

use App\Services\ChecksheetIntegrityService;
use Illuminate\Database\Eloquent\Model;

class ComponentChecksheet extends Model
{
    protected $fillable = [
        'comp_id',
        'stage_number',
        'items',
        'answers',
        'filled_by',
        'completed_at',
    ];

    protected $casts = [
        'items'        => 'array',
        'answers'      => 'array',
        'completed_at' => 'datetime',
    ];

    public function component()
    {
        return $this->belongsTo(Component::class, 'comp_id', 'comp_id');
    }

    public function filledByUser()
    {
        return $this->belongsTo(User::class, 'filled_by');
    }

    /**
     * Hitung persentase progress pengisian checksheet.
     */
    public function getProgressAttribute(): int
    {
        $integrity = app(ChecksheetIntegrityService::class);
        $totalItems = count($integrity->validItemIds($this));

        if ($totalItems === 0) {
            return 0;
        }

        $answeredItems = $integrity->answeredCount($this);

        return (int) round(($answeredItems / $totalItems) * 100);
    }

    /**
     * Cek apakah checksheet sudah selesai (100%).
     */
    public function getIsCompleteAttribute(): bool
    {
        return app(ChecksheetIntegrityService::class)->isFullyAnswered($this);
    }
}
