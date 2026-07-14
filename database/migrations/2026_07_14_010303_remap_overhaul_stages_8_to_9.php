<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Remap overhaul stages from 8-stage to 9-stage system.
 * 
 * Old mapping:                    New mapping:
 * 1 Receiving                 →   1 Receiving (Penerimaan DC)
 * 2 Disassembly               →   2 Disassembling (Pembongkaran)
 *                                 3 Washing (Pencucian) ← NEW
 * 3 Measuring & Inspection    →   4 Measurement & Inspection
 * 4 Repair / Machining        →   5 Machining & Fabrication
 * 5 Assembly                  →   6 Assembly
 * 6 Test Bench                →   7 Test Performance
 * 7 Painting & Finishing      →   8 Painting
 * 8 Delivery (RFU)            →   9 Final Inspection (RFU)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Remap stages in reverse order to avoid conflicts
        // (e.g., stage 3 → 4 before stage 4 → 5, etc.)

        // Components table: remap current_stage
        // Process from highest to lowest to prevent overwriting
        DB::table('components')->where('current_stage', 8)->update(['current_stage' => 9]);
        DB::table('components')->where('current_stage', 7)->update(['current_stage' => 8]);
        DB::table('components')->where('current_stage', 6)->update(['current_stage' => 7]);
        DB::table('components')->where('current_stage', 5)->update(['current_stage' => 6]);
        DB::table('components')->where('current_stage', 4)->update(['current_stage' => 5]);
        DB::table('components')->where('current_stage', 3)->update(['current_stage' => 4]);
        // Stage 1 and 2 remain the same

        // Overhaul logs table: remap stage_number
        DB::table('overhaul_logs')->where('stage_number', 8)->update(['stage_number' => 9]);
        DB::table('overhaul_logs')->where('stage_number', 7)->update(['stage_number' => 8]);
        DB::table('overhaul_logs')->where('stage_number', 6)->update(['stage_number' => 7]);
        DB::table('overhaul_logs')->where('stage_number', 5)->update(['stage_number' => 6]);
        DB::table('overhaul_logs')->where('stage_number', 4)->update(['stage_number' => 5]);
        DB::table('overhaul_logs')->where('stage_number', 3)->update(['stage_number' => 4]);
        // Stage 1 and 2 remain the same

        // Update components table comment for current_stage range
        Schema::table('components', function (Blueprint $table) {
            $table->integer('current_stage')->default(1)->comment('1-9')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse: remap from 9-stage back to 8-stage
        // Process from lowest to highest
        DB::table('components')->where('current_stage', 4)->update(['current_stage' => 3]);
        DB::table('components')->where('current_stage', 5)->update(['current_stage' => 4]);
        DB::table('components')->where('current_stage', 6)->update(['current_stage' => 5]);
        DB::table('components')->where('current_stage', 7)->update(['current_stage' => 6]);
        DB::table('components')->where('current_stage', 8)->update(['current_stage' => 7]);
        DB::table('components')->where('current_stage', 9)->update(['current_stage' => 8]);
        // Delete any records at stage 3 (Washing) since it didn't exist before
        DB::table('overhaul_logs')->where('stage_number', 3)->delete();

        DB::table('overhaul_logs')->where('stage_number', 4)->update(['stage_number' => 3]);
        DB::table('overhaul_logs')->where('stage_number', 5)->update(['stage_number' => 4]);
        DB::table('overhaul_logs')->where('stage_number', 6)->update(['stage_number' => 5]);
        DB::table('overhaul_logs')->where('stage_number', 7)->update(['stage_number' => 6]);
        DB::table('overhaul_logs')->where('stage_number', 8)->update(['stage_number' => 7]);
        DB::table('overhaul_logs')->where('stage_number', 9)->update(['stage_number' => 8]);

        Schema::table('components', function (Blueprint $table) {
            $table->integer('current_stage')->default(1)->comment('1-8')->change();
        });
    }
};
