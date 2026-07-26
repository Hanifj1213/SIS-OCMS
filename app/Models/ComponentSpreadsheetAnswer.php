<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComponentSpreadsheetAnswer extends Model
{
    protected $primaryKey = 'answer_id';

    protected $fillable = [
        'comp_id',
        'layout_id',
        'sheet',
        'cell_ref',
        'value',
        'filled_by',
    ];

    public function component()
    {
        return $this->belongsTo(Component::class, 'comp_id', 'comp_id');
    }

    public function layout()
    {
        return $this->belongsTo(SpreadsheetLayout::class, 'layout_id', 'layout_id');
    }

    public function filledByUser()
    {
        return $this->belongsTo(User::class, 'filled_by');
    }

    /** Nilai centang disimpan sebagai '1'; selain itu dianggap tidak dicentang. */
    public function isChecked(): bool
    {
        return $this->value === '1';
    }
}
