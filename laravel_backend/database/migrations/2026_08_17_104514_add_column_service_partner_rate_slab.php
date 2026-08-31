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
        Schema::table('service_partner_rate_slab', function (Blueprint $table) {
            $table->integer('min_transit_days')->nullable()->after('currency')->default(1);
            $table->integer('max_transit_days')->nullable()->after('min_transit_days')->default(5);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_partner_rate_slab', function (Blueprint $table) {
            $table->dropColumn(['min_transit_days', 'max_transit_days']);
        });
    }
};
