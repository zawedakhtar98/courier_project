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
        Schema::create('customs_invoice', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('shipments_details')->cascadeOnDelete();
            $table->string('csb_type', 50)->nullable();
            $table->string('invoice_number', 100)->nullable();
            $table->date('invoice_date')->nullable();
            $table->string('term_of_trade', 50)->nullable();
            $table->string('reason_for_export', 255)->nullable();
            $table->string('currency', 10)->default('INR');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customs_invoice');
    }
};
