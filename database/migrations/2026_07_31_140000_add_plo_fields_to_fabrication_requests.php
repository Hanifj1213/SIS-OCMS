<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fabrication_requests', function (Blueprint $table) {
            $table->string('ro_number')->nullable()->after('fr_number');
            $table->string('pr_number')->nullable()->after('ro_number');
            $table->date('request_date')->nullable()->after('pr_number');
            $table->date('estimation_date')->nullable()->after('request_date');
            $table->string('location_site')->nullable()->after('estimation_date');
            $table->string('work_order_for')->nullable()->after('location_site');
            $table->string('sent_to')->nullable()->after('work_order_for');
            $table->string('attn')->nullable()->after('sent_to');
            // Part material table (satu baris per FR — satu part satu FR)
            $table->string('brand')->nullable()->after('attn');
            $table->decimal('unit_price', 15, 2)->nullable()->after('brand');
            $table->decimal('labour_cost', 15, 2)->nullable()->after('unit_price');
            $table->text('note')->nullable()->after('labour_cost');
        });
    }

    public function down(): void
    {
        Schema::table('fabrication_requests', function (Blueprint $table) {
            $table->dropColumn([
                'ro_number', 'pr_number', 'request_date', 'estimation_date',
                'location_site', 'work_order_for', 'sent_to', 'attn',
                'brand', 'unit_price', 'labour_cost', 'note',
            ]);
        });
    }
};
