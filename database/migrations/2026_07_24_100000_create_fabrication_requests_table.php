<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fabrication_requests', function (Blueprint $table) {
            $table->id('fr_id');
            $table->unsignedBigInteger('comp_id');
            $table->string('fr_number')->unique();
            $table->string('part_number')->nullable();
            $table->string('part_name');
            $table->unsignedInteger('qty')->default(1);
            $table->enum('work_type', ['repair', 'fabrikasi', 'modifikasi'])->default('repair');
            $table->text('instruction')->nullable();
            $table->enum('source', ['form', 'gsheet', 'manual'])->default('manual');
            $table->enum('status', ['draft', 'printed', 'done'])->default('draft');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('comp_id')->references('comp_id')->on('components')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['comp_id', 'part_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fabrication_requests');
    }
};
