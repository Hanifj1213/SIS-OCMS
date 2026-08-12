<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Simpan garis, panah, dan teks ukuran sebagai objek vektor. Koordinat
     * menggunakan persen terhadap area "Gambar & Dimensi" agar anotasi tetap
     * mengikuti gambar saat form dirender ke ukuran layar atau PDF.
     */
    public function up(): void
    {
        Schema::table('fabrication_requests', function (Blueprint $table) {
            $table->json('annotations')->nullable()->after('images');
        });
    }

    public function down(): void
    {
        Schema::table('fabrication_requests', function (Blueprint $table) {
            $table->dropColumn('annotations');
        });
    }
};
