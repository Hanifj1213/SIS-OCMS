<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('components', function (Blueprint $table) {
            // Field Way Bill digabung ke Manifest; slot lama dipakai untuk nomor RO
            $table->string('ro_number')->nullable()->after('way_bill');
            // Dokumen/foto tahap Assembly (JSON list, seperti painting_images)
            $table->json('assembly_documents')->nullable()->after('painting_images');
        });

        Schema::table('overhaul_logs', function (Blueprint $table) {
            // Jejak approval: siapa mengajukan, siapa menyetujui, kapan
            $table->unsignedBigInteger('approval_requested_by')->nullable()->after('notes');
            $table->timestamp('approval_requested_at')->nullable()->after('approval_requested_by');
            $table->unsignedBigInteger('approved_by')->nullable()->after('approval_requested_at');
            $table->timestamp('approved_at')->nullable()->after('approved_by');

            $table->foreign('approval_requested_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('overhaul_logs', function (Blueprint $table) {
            $table->dropForeign(['approval_requested_by']);
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['approval_requested_by', 'approval_requested_at', 'approved_by', 'approved_at']);
        });

        Schema::table('components', function (Blueprint $table) {
            $table->dropColumn(['ro_number', 'assembly_documents']);
        });
    }
};
