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
        Schema::create('components', function (Blueprint $table) {
            $table->id('comp_id');
            $table->string('serial_number')->unique();
            $table->string('model_type');
            $table->integer('current_stage')->default(1); // 1-8
            $table->string('qr_code_path')->nullable();
            $table->string('status')->default('On Progress'); // On Progress / RFU
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('components');
    }
};
