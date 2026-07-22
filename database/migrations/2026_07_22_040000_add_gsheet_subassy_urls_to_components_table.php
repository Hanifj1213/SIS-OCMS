<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('components', function (Blueprint $table) {
            $table->string('gsheet_subassy_disassembly_url')->nullable()->after('gsheet_measurement_url');
            $table->string('gsheet_subassy_measurement_url')->nullable()->after('gsheet_subassy_disassembly_url');
        });
    }

    public function down(): void
    {
        Schema::table('components', function (Blueprint $table) {
            $table->dropColumn([
                'gsheet_subassy_disassembly_url',
                'gsheet_subassy_measurement_url',
            ]);
        });
    }
};
