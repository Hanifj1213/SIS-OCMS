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
        Schema::create('checksheet_templates', function (Blueprint $table) {
            $table->id();
            $table->string('major_category');       // Engine, TC/Transmission, etc.
            $table->integer('stage_number');         // 1 = Receiving, 2 = Disassembly, etc.
            $table->string('template_name');         // Human-readable name
            $table->json('items');                   // Array of checksheet item definitions
            $table->timestamps();

            $table->unique(['major_category', 'stage_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checksheet_templates');
    }
};
