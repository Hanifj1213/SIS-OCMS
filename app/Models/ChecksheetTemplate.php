<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChecksheetTemplate extends Model
{
    protected $fillable = [
        'major_category',
        'stage_number',
        'template_name',
        'items',
    ];

    protected $casts = [
        'items' => 'array',
    ];
}
