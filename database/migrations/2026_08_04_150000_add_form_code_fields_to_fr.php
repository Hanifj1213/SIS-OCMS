<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Blok kode formulir di kanan atas (No. Formulir, No. SOP, Pemilik,
     * Revisi) semula ditulis mati di view. Nilainya bisa berubah saat SOP
     * direvisi, jadi disimpan per FR dengan nilai bawaan seperti form asli.
     */
    public function up(): void
    {
        Schema::table('fabrication_requests', function (Blueprint $table) {
            $table->string('form_no')->nullable()->after('fr_number');
            $table->string('sop_no')->nullable()->after('form_no');
            $table->string('form_owner')->nullable()->after('sop_no');
            $table->string('form_revision')->nullable()->after('form_owner');
        });
    }

    public function down(): void
    {
        Schema::table('fabrication_requests', function (Blueprint $table) {
            $table->dropColumn(['form_no', 'sop_no', 'form_owner', 'form_revision']);
        });
    }
};
