<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FabricationRequest extends Model
{
    use HasFactory;

    protected $primaryKey = 'fr_id';

    protected $fillable = [
        'comp_id',
        'fr_number',
        'part_number',
        'part_name',
        'section',
        'qty',
        'work_type',
        'instruction',
        'source',
        'status',
        'created_by',
    ];

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
