<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('overhaul_logs', function (Blueprint $table) {
            $table->id('log_id');
            $table->unsignedBigInteger('comp_id');
            $table->integer('stage_number');
            $table->unsignedBigInteger('mechanic_id')->nullable();
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->foreign('comp_id')->references('comp_id')->on('components')->onDelete('cascade');
            $table->foreign('mechanic_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('overhaul_logs');
    }
};
