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
        Schema::create('inspection_details', function (Blueprint $table) {
            $table->id('insp_id');
            $table->unsignedBigInteger('comp_id');
            $table->string('part_name');
            $table->string('standard_value')->nullable();
            $table->string('actual_value')->nullable();
            $table->enum('decision', ['Reused', 'Repair', 'Replace'])->nullable();
            $table->timestamps();
            
            $table->foreign('comp_id')->references('comp_id')->on('components')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inspection_details');
    }
};
