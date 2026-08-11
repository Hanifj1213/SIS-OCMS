<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Mapping template Google Sheets per kind × kategori × EGI.
 * Menggantikan config/checksheet_gsheets.php agar bisa dikelola dari UI.
 */
class GsheetTemplate extends Model
{
    protected $fillable = [
        'kind',
        'major_category',
        'egi',
        'spreadsheet_id',
    ];

    /** Label kind untuk UI. */
    public const KIND_LABELS = [
        'disassembly' => 'Disassembly',
        'measurement' => 'Measurement / Inspection',
        'subassy_disassembly' => 'Sub-Assy Disassembly',
        'subassy_measurement' => 'Sub-Assy Measurement',
        'sdr' => 'SDR',
        'assembly' => 'Assembly (Stage 4)',
        'testbench' => 'Test Bench (Stage 5)',
    ];

    public function kindLabel(): string
    {
        return self::KIND_LABELS[$this->kind] ?? $this->kind;
    }
}
