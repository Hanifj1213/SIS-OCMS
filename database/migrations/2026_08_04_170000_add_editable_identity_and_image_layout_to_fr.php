<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Unit Model / Component model / Unit Code semula selalu ikut data
     * komponen dan tidak bisa disunting. Di lapangan isian pada form kadang
     * berbeda dari master (mis. penulisan "D 155-6" vs "D155-6"), jadi
     * nilainya disimpan per FR. Kosong = pakai data komponen.
     *
     * image_layout menyimpan posisi & ukuran gambar pada kolom "Gambar &
     * Dimensi" agar bisa digeser dan diubah ukurannya seperti di Word.
     */
    public function up(): void
    {
        Schema::table('fabrication_requests', function (Blueprint $table) {
            $table->string('unit_model')->nullable()->after('location_site');
            $table->string('component_model')->nullable()->after('unit_model');
            $table->string('unit_code')->nullable()->after('component_model');
            $table->json('image_layout')->nullable()->after('image_path_2');
        });
    }

    public function down(): void
    {
        Schema::table('fabrication_requests', function (Blueprint $table) {
            $table->dropColumn(['unit_model', 'component_model', 'unit_code', 'image_layout']);
        });
    }
};
