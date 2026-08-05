<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('part_requests', function (Blueprint $table) {
            $table->string('wo_number')->nullable()->after('comp_id');
            $table->string('figure')->nullable()->after('part_name');
            $table->string('index_no')->nullable()->after('figure');
            $table->string('part_number')->nullable()->after('index_no');
            $table->string('order_code')->default('A')->after('qty'); // Code A-K (MOL standard)
            $table->text('remarks')->nullable()->after('order_code');
        });

        Schema::table('fabrication_requests', function (Blueprint $table) {
            $table->timestamp('completed_at')->nullable()->after('status');
            $table->text('completion_notes')->nullable()->after('completed_at');
        });
    }

    public function down(): void
    {
        Schema::table('part_requests', function (Blueprint $table) {
            $table->dropColumn(['wo_number', 'figure', 'index_no', 'part_number', 'order_code', 'remarks']);
        });

        Schema::table('fabrication_requests', function (Blueprint $table) {
            $table->dropColumn(['completed_at', 'completion_notes']);
        });
    }
};
