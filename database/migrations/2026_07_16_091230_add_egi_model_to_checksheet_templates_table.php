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
        Schema::table('checksheet_templates', function (Blueprint $table) {
            $table->string('egi_model')->nullable()->after('major_category');
            $table->dropUnique(['major_category', 'stage_number']);
            $table->unique(['major_category', 'egi_model', 'stage_number'], 'ct_category_egi_stage_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('checksheet_templates', function (Blueprint $table) {
            $table->dropUnique('ct_category_egi_stage_unique');
            $table->dropColumn('egi_model');
        });
    }
};
