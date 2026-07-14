<?php

namespace App\Models;

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
        $totalItems = count($this->items ?? []);
        $answeredItems = count($this->answers ?? []);

        return $totalItems > 0 ? (int) round(($answeredItems / $totalItems) * 100) : 0;
    }

    /**
     * Cek apakah checksheet sudah selesai (100%).
     */
    public function getIsCompleteAttribute(): bool
    {
        return $this->progress === 100;
    }
}
