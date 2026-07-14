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
        Schema::create('part_requests', function (Blueprint $table) {
            $table->id('req_id');
            $table->unsignedBigInteger('comp_id');
            $table->string('part_name');
            $table->integer('qty')->default(1);
            $table->enum('status', ['Pending', 'Available', 'Out of Stock'])->default('Pending');
            $table->timestamps();
            
            $table->foreign('comp_id')->references('comp_id')->on('components')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('part_requests');
    }
};
