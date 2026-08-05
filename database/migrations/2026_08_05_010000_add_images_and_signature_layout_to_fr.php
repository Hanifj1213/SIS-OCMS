<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom "Gambar & Dimensi" pada form asli kadang memuat lebih dari dua
     * foto (sampai lima). Dua kolom tetap (image_path, image_path_2) tidak
     * cukup, jadi gambar disimpan sebagai daftar: tiap entri berisi path
     * plus posisi & ukuran (persen).
     *
     * signature_layout menyimpan posisi & ukuran gambar tanda tangan agar
     * bisa digeser/diubah ukuran seperti gambar part.
     */
    public function up(): void
    {
        Schema::table('fabrication_requests', function (Blueprint $table) {
            $table->json('images')->nullable()->after('image_layout');
            $table->json('signature_layout')->nullable()->after('signatures');
        });
    }

    public function down(): void
    {
        Schema::table('fabrication_requests', function (Blueprint $table) {
            $table->dropColumn(['images', 'signature_layout']);
        });
    }
};
