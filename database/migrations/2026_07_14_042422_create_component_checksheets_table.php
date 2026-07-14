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
        Schema::create('component_checksheets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('comp_id');
            $table->integer('stage_number');
            $table->json('items');               // Copy from template + custom items
            $table->json('answers')->nullable(); // {"RCV-001": "good", "RCV-002": "bad", ...}
            $table->unsignedBigInteger('filled_by')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('comp_id')->references('comp_id')->on('components')->onDelete('cascade');
            $table->foreign('filled_by')->references('id')->on('users')->nullOnDelete();
            $table->unique(['comp_id', 'stage_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('component_checksheets');
    }
};
