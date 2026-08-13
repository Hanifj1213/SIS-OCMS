<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Integritas transisi tahap:
     *
     * - satu log tahap aktif per (comp, stage): mencegah dua transisi/approval
     *   membuka dua log berjalan untuk tahap yang sama;
     * - satu permintaan approval aktif per komponen: dua mekanik tidak bisa
     *   mengajukan approval "menunggu" dua kali pada tahap yang sama.
     *
     * Catatan: duplicate approval "semua di dalam transaksi yang sama" juga
     * dijaga oleh lockForUpdate() + sidik jari approval di
     * StageTransitionService — constraint ini lapisan terakhir di DB.
     */
    public function up(): void
    {
        Schema::table('overhaul_logs', function (Blueprint $table) {
            $table->unique(
                ['comp_id', 'stage_number', 'end_time'],
                'overhaul_logs_one_open_per_stage'
            );
        });

        Schema::table('components', function (Blueprint $table) {
            $table->unique(['comp_id', 'is_waiting_approval'], 'components_one_pending_approval');
        });
    }

    public function down(): void
    {
        Schema::table('components', function (Blueprint $table) {
            $table->dropUnique('components_one_pending_approval');
        });

        Schema::table('overhaul_logs', function (Blueprint $table) {
            $table->dropUnique('overhaul_logs_one_open_per_stage');
        });
    }
};
