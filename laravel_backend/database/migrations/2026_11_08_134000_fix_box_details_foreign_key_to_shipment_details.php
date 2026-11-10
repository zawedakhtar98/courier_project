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
        Schema::table('box_details', function (Blueprint $table) {
            // Drop old foreign key constraint that pointed to 'shipments_details'
            $table->dropForeign('box_details_shipment_id_foreign');

            // Re-add foreign key constraint pointing to the correct 'shipment_details' table
            $table->foreign('shipment_id')
                ->references('id')
                ->on('shipment_details')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('box_details', function (Blueprint $table) {
            $table->dropForeign(['shipment_id']);
            $table->foreign('shipment_id')
                ->references('id')
                ->on('shipments_details')
                ->cascadeOnDelete();
        });
    }
};

