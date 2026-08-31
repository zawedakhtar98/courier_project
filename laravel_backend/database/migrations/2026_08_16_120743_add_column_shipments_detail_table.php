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
        Schema::table('shipments_details', function (Blueprint $table) {
            $table->date('pickup_date')->after('id');
            $table->date('estimated_delivery_date')->after('shipment_total_cost')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipments_details', function (Blueprint $table) {
            $table->dropColumn(['pickup_date', 'estimated_delivery_date']);
        });
    }
};
