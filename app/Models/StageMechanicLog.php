<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Segmen kerja crew pada satu tahap overhaul: "N mekanik bekerja dari
 * clock_in sampai clock_out". user_id adalah PIC yang mencatat, bukan
 * mekanik per orang — Man Hour = work hour segmen x crew_count.
 */
class StageMechanicLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'comp_id',
        'stage_number',
        'user_id',
        'crew_count',
        'crew_names',
        'clock_in',
        'clock_out',
    ];

    protected $casts = [
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
        'crew_count' => 'integer',
    ];

    public function component()
    {
        return $this->belongsTo(Component::class, 'comp_id', 'comp_id');
    }

    public function mechanic()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
