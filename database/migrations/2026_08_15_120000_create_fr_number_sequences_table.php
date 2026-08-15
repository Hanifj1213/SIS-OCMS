<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fr_number_sequences', function (Blueprint $table) {
            $table->unsignedSmallInteger('year')->primary();
            $table->unsignedInteger('last_number')->default(0);
        });

        $byYear = [];
        $numbers = DB::table('fabrication_requests')
            ->where('fr_number', 'like', 'FR/SIS/RC/%')
            ->pluck('fr_number');

        foreach ($numbers as $number) {
            if (preg_match('#FR/SIS/RC/(\d+)/[^/]+/(\d{4})/INT#', (string) $number, $matches)) {
                $year = (int) $matches[2];
                $seq = (int) $matches[1];
                $byYear[$year] = max($byYear[$year] ?? 0, $seq);
            }
        }

        foreach ($byYear as $year => $lastNumber) {
            DB::table('fr_number_sequences')->insert([
                'year' => $year,
                'last_number' => $lastNumber,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fr_number_sequences');
    }
};
