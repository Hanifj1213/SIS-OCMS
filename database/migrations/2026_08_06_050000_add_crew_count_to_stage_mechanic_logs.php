<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Model crew berbasis JUMLAH ORANG, bukan akun per mekanik.
     * Dengan ratusan mekanik, PIC cukup mencatat "5 orang mengerjakan
     * tahap ini" — user_id menjadi pencatat (PIC), crew_count jumlah
     * mekanik yang bekerja pada segmen waktu tersebut.
     */
    public function up(): void
    {
        Schema::table('stage_mechanic_logs', function (Blueprint $table) {
            $table->unsignedInteger('crew_count')->default(1)->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('stage_mechanic_logs', function (Blueprint $table) {
            $table->dropColumn('crew_count');
        });
    }
};
