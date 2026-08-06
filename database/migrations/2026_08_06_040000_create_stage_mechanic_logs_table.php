<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel transaksional untuk Man Hour: satu baris = satu sesi kerja
     * seorang mekanik pada satu tahap. Mekanik bisa clock-in/clock-out
     * bergantian shift, sehingga Man Hour dihitung dinamis dari akumulasi
     * sesi, bukan sekadar "jumlah mekanik x durasi tahap".
     */
    public function up(): void
    {
        Schema::create('stage_mechanic_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('comp_id');
            $table->integer('stage_number');
            $table->unsignedBigInteger('user_id');
            $table->timestamp('clock_in');
            $table->timestamp('clock_out')->nullable();
            $table->timestamps();

            $table->foreign('comp_id')->references('comp_id')->on('components')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['comp_id', 'stage_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stage_mechanic_logs');
    }
};
