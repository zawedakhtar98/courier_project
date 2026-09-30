<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_additional_charges', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('service_partner_id')->comment('this is mapped with service_partner_rate_slab table');
            $table->unsignedBigInteger('charge_type_id');

            $table->decimal('value', 10, 2)->default(0.00);

            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->integer('sort_order')->default(0)->comment('Execution order for the calculation loop');

            $table->timestamps();

            $table->unique(['service_partner_id', 'charge_type_id'], 'sp_zone_charges_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_additional_charges');
    }
};
