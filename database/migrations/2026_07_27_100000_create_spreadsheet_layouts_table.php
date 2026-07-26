<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Layout checksheet hasil impor dari .xlsx, supaya tampilannya bisa
 * disajikan lokal (1:1 seperti Excel) tanpa lewat Google Sheets.
 *
 * `layout`       — sel, gaya, merge, lebar kolom, gambar (dipakai renderer)
 * `decision_map` — hasil pencarian keyword REUSE/SALVAGE/REPLACE atau
 *                  U/A|U/R|R/N sekali saat impor, supaya saat mengisi dan
 *                  saat membuat FR tidak perlu menebak kolom lagi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spreadsheet_layouts', function (Blueprint $table) {
            $table->id('layout_id');
            $table->string('major_category');
            $table->string('egi_model')->nullable();
            $table->string('kind');                 // disassembly | measurement | inspection | subassy_*
            $table->string('source_file');
            $table->json('layout');
            $table->json('decision_map')->nullable();
            $table->unsignedInteger('sheet_count')->default(0);
            $table->unsignedInteger('part_row_count')->default(0);
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();

            $table->unique(['major_category', 'egi_model', 'kind'], 'sl_category_egi_kind_unique');
        });

        Schema::create('component_spreadsheet_answers', function (Blueprint $table) {
            $table->id('answer_id');
            $table->foreignId('comp_id')->constrained('components', 'comp_id')->cascadeOnDelete();
            $table->foreignId('layout_id')->constrained('spreadsheet_layouts', 'layout_id')->cascadeOnDelete();
            $table->string('sheet');
            $table->string('cell_ref', 16);
            $table->text('value')->nullable();
            $table->foreignId('filled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['comp_id', 'layout_id', 'sheet', 'cell_ref'], 'csa_cell_unique');
            $table->index(['comp_id', 'layout_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('component_spreadsheet_answers');
        Schema::dropIfExists('spreadsheet_layouts');
    }
};
