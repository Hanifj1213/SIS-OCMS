<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FrNumberSequence extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $primaryKey = 'year';

    protected $fillable = [
        'year',
        'last_number',
    ];

    protected $casts = [
        'year' => 'integer',
        'last_number' => 'integer',
    ];
}
