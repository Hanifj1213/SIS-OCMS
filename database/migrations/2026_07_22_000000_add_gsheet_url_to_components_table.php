<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('components', function (Blueprint $table) {
            // URL Google Sheets checksheet disassembly milik komponen ini
            // (hasil duplikasi otomatis dari template per-EGI)
            $table->string('gsheet_url')->nullable()->after('qr_code_path');
        });
    }

    public function down(): void
    {
        Schema::table('components', function (Blueprint $table) {
            $table->dropColumn('gsheet_url');
        });
    }
};
