<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Header MOL (Mechanic Order List) mengikuti tab "ADD 1" pada MOL.xlsx.
     * Satu komponen = satu MOL, jadi header cukup menempel di komponen.
     */
    public function up(): void
    {
        Schema::table('components', function (Blueprint $table) {
            $table->string('mol_wo_number')->nullable()->after('gsheet_subassy_measurement_url');
            $table->string('mol_order_type')->nullable()->after('mol_wo_number');
            $table->date('mol_order_date')->nullable()->after('mol_order_type');
            $table->string('mol_ir_number')->nullable()->after('mol_order_date');
            $table->date('mol_ir_date')->nullable()->after('mol_ir_number');
            $table->text('mol_note')->nullable()->after('mol_ir_date');
        });
    }

    public function down(): void
    {
        Schema::table('components', function (Blueprint $table) {
            $table->dropColumn([
                'mol_wo_number', 'mol_order_type', 'mol_order_date',
                'mol_ir_number', 'mol_ir_date', 'mol_note',
            ]);
        });
    }
};
