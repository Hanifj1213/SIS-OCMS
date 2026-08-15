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
        'egi',
        'unit_code',
        'unit_serial_no',
        'site_district',
        'model_type',
        'major_category',
        'component_model',
        'pn_assy',
        'status_ovh',
        'core_category',
        'smr',
        'life_time',
        'date_defitted',
        'manifest',
        'way_bill',
        'ro_number',
        'date_delivery',
        'current_stage',
        'is_waiting_approval',
        'qr_code_path',
        'gsheet_url',
        'gsheet_measurement_url',
        'gsheet_subassy_disassembly_url',
        'gsheet_subassy_measurement_url',
        'gsheet_sdr_url',
        'gsheet_assembly_url',
        'gsheet_testbench_url',
        'painting_images',
        'assembly_documents',
        'mol_wo_number',
        'mol_order_type',
        'mol_order_date',
        'mol_ir_number',
        'mol_ir_date',
        'mol_note',
        'mol_document_path',
        'status',
    ];

    protected $casts = [
        'is_waiting_approval' => 'boolean',
        'painting_images' => 'array',
        'assembly_documents' => 'array',
        'date_defitted' => 'date',
        'date_delivery' => 'date',
        'smr' => 'integer',
        'life_time' => 'integer',
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

    public function fabricationRequests()
    {
        return $this->hasMany(FabricationRequest::class, 'comp_id', 'comp_id');
    }

    public function checksheets()
    {
        return $this->hasMany(ComponentChecksheet::class, 'comp_id', 'comp_id');
    }

    public function mechanicLogs()
    {
        return $this->hasMany(StageMechanicLog::class, 'comp_id', 'comp_id');
    }
}
