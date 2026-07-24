<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Satu komponen bisa punya beberapa unit sejenis dalam satu workbook
 * (Control Valve NO1/NO2/NO3, Disassembly LH/RH, Cyl Head vs Turbo).
 * `section` menyimpan nama tab asal supaya part dengan nama sama pada unit
 * berbeda tidak saling dianggap duplikat.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fabrication_requests', function (Blueprint $table) {
            $table->string('section')->nullable()->after('part_name');
        });

        Schema::table('part_requests', function (Blueprint $table) {
            $table->string('section')->nullable()->after('part_name');
        });
    }

    public function down(): void
    {
        Schema::table('fabrication_requests', function (Blueprint $table) {
            $table->dropColumn('section');
        });

        Schema::table('part_requests', function (Blueprint $table) {
            $table->dropColumn('section');
        });
    }
};
