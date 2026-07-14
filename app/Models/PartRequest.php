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
        'part_name',
        'qty',
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
