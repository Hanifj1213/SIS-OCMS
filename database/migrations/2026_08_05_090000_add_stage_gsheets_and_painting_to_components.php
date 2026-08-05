<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stage 4 (Assembly) & Stage 5 (Test Bench) memakai checksheet Google Sheets
 * seperti Disassembly. Stage 6 (Painting) berupa dokumentasi foto.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('components', function (Blueprint $table) {
            $table->string('gsheet_assembly_url')->nullable()->after('gsheet_sdr_url');
            $table->string('gsheet_testbench_url')->nullable()->after('gsheet_assembly_url');
            // Daftar foto dokumentasi painting: [{path, uploaded_at}, ...]
            $table->text('painting_images')->nullable()->after('gsheet_testbench_url');
        });
    }

    public function down(): void
    {
        Schema::table('components', function (Blueprint $table) {
            $table->dropColumn(['gsheet_assembly_url', 'gsheet_testbench_url', 'painting_images']);
        });
    }
};
