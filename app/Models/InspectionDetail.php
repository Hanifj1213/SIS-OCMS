<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InspectionDetail extends Model
{
    use HasFactory;

    protected $primaryKey = 'insp_id';

    protected $fillable = [
        'comp_id',
        'part_name',
        'standard_value',
        'actual_value',
        'decision',
    ];

    public function component()
    {
        return $this->belongsTo(Component::class, 'comp_id', 'comp_id');
    }
}
