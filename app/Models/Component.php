<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Component extends Model
{
    use HasFactory;

    protected $primaryKey = 'comp_id';

    protected $fillable = [
        'serial_number',
        'model_type',
        'major_category',
        'current_stage',
        'qr_code_path',
        'status',
    ];

    /**
     * Mendapatkan key name untuk route model binding.
     * Ini WAJIB agar Route::resource & {component} binding
     * menggunakan 'comp_id' bukan default 'id'.
     */
    public function getRouteKeyName(): string
    {
        return 'comp_id';
    }

    public function overhaulLogs()
    {
        return $this->hasMany(OverhaulLog::class, 'comp_id', 'comp_id');
    }

    public function inspectionDetails()
    {
        return $this->hasMany(InspectionDetail::class, 'comp_id', 'comp_id');
    }

    public function partRequests()
    {
        return $this->hasMany(PartRequest::class, 'comp_id', 'comp_id');
    }

    public function checksheets()
    {
        return $this->hasMany(ComponentChecksheet::class, 'comp_id', 'comp_id');
    }
}
