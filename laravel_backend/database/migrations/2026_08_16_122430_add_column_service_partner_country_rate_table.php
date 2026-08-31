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
        Schema::table('service_partner_country_rate', function (Blueprint $table) {
            $table->integer('min_delivery_days')->after('service_code')->nullable();
            $table->integer('max_delivery_days')->after('min_delivery_days')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_partner_country_rate', function (Blueprint $table) {
            $table->dropColumn(['min_delivery_days', 'max_delivery_days']);
        });
    }
};
