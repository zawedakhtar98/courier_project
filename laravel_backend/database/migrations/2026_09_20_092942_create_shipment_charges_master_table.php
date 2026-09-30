<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_charges_master', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('e.g., Fuel Surcharge');
            $table->string('code')->comment('e.g., FS');
            $table->text('description')->nullable();
            $table->enum('calculation_method', ['flat', 'percentage', 'fixed', 'per_kg']); // we can consider flat as the slab rate
            $table->enum('calculation_basis', ['weight', 'freight', 'shipment_value', 'subtotal'])->comment('weight: weight of the shipment, freight: freight charge, shipment: shipment value, subtotal: subtotal of the shipment');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_charges_master');
    }
};
