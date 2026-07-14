<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OverhaulLog extends Model
{
    use HasFactory;

    protected $primaryKey = 'log_id';

    protected $fillable = [
        'comp_id',
        'stage_number',
        'mechanic_id',
        'start_time',
        'end_time',
        'notes',
    ];

    public function component()
    {
        return $this->belongsTo(Component::class, 'comp_id', 'comp_id');
    }

    public function mechanic()
    {
        return $this->belongsTo(User::class, 'mechanic_id', 'id');
    }
}
