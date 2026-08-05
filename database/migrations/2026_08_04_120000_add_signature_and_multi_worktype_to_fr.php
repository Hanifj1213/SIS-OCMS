<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Form asli PLO/09/F-021 memakai KOTAK CENTANG untuk jenis pekerjaan —
     * lebih dari satu boleh ditandai (contoh nyata: Repair + Fabrikasi), jadi
     * satu kolom enum tidak cukup. Kolom `work_type` lama dipertahankan sebagai
     * pilihan utama supaya kode & data lama tetap jalan.
     *
     * Form asli juga memuat blok tanda tangan (gambar, nama, tanggal, teks
     * "FOR") pada lima kolom approval, serta dua foto pada kolom gambar.
     */
    public function up(): void
    {
        Schema::table('fabrication_requests', function (Blueprint $table) {
            $table->json('work_types')->nullable()->after('work_type');
            $table->string('address')->nullable()->after('sent_to');
            $table->string('image_path_2')->nullable()->after('image_path');
            $table->json('signatures')->nullable()->after('note');
        });

        // 'work_type' semula enum(repair, fabrikasi, modifikasi). Form asli juga
        // punya pilihan "Others", jadi kolomnya dilonggarkan menjadi string.
        // SQLite tidak menegakkan enum, tapi MySQL/Postgres perlu perubahan ini.
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            Schema::table('fabrication_requests', function (Blueprint $table) {
                $table->string('work_type')->default('repair')->change();
            });
        }
    }

    public function down(): void
    {
        Schema::table('fabrication_requests', function (Blueprint $table) {
            $table->dropColumn(['work_types', 'address', 'image_path_2', 'signatures']);
        });
    }
};
