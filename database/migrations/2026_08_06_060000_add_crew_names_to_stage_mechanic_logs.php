<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nama-nama mekanik per segmen crew (teks bebas, dipisah koma).
     * Pelengkap crew_count — tidak butuh akun user per mekanik.
     */
    public function up(): void
    {
        Schema::table('stage_mechanic_logs', function (Blueprint $table) {
            $table->text('crew_names')->nullable()->after('crew_count');
        });
    }

    public function down(): void
    {
        Schema::table('stage_mechanic_logs', function (Blueprint $table) {
            $table->dropColumn('crew_names');
        });
    }
};
