<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartRequest extends Model
{
    use HasFactory;

    protected $primaryKey = 'req_id';

    protected $fillable = [
        'comp_id',
        'wo_number',
        'part_name',
        'figure',
        'index_no',
        'part_number',
        'section',
        'qty',
        'order_code',
        'remarks',
        'status',
    ];

    /**
     * Route model binding key.
     */
    public function getRouteKeyName(): string
    {
        return 'req_id';
    }

    public function component()
    {
        return $this->belongsTo(Component::class, 'comp_id', 'comp_id');
    }
}
