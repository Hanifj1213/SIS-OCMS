<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('components', function (Blueprint $table) {
            // Data Unit
            $table->string('egi')->nullable()->after('serial_number');
            $table->string('unit_code')->nullable()->after('egi');
            $table->string('unit_serial_no')->nullable()->after('unit_code');
            $table->string('site_district')->nullable()->after('unit_serial_no');

            // Data Komponen
            $table->string('component_model')->nullable()->after('major_category');
            $table->string('pn_assy')->nullable()->after('component_model');
            $table->string('status_ovh')->nullable()->after('pn_assy');

            // Informasi Operasional
            $table->integer('smr')->nullable()->after('status_ovh');
            $table->integer('life_time')->nullable()->after('smr');
            $table->date('date_defitted')->nullable()->after('life_time');

            // Logistik
            $table->string('manifest')->nullable()->after('date_defitted');
            $table->string('way_bill')->nullable()->after('manifest');
            $table->date('date_delivery')->nullable()->after('way_bill');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('components', function (Blueprint $table) {
            $table->dropColumn([
                'egi', 'unit_code', 'unit_serial_no', 'site_district',
                'component_model', 'pn_assy', 'status_ovh',
                'smr', 'life_time', 'date_defitted',
                'manifest', 'way_bill', 'date_delivery',
            ]);
        });
    }
};
