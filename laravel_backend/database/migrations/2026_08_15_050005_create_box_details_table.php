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
        Schema::create('box_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('shipments_details')->cascadeOnDelete();
            $table->integer('no_of_box')->nullable();
            $table->decimal('length', 10, 2)->nullable();
            $table->decimal('width', 10, 2)->nullable();
            $table->decimal('height', 10, 2)->nullable();
            $table->decimal('actual_weight', 10, 2)->nullable();
            $table->decimal('volumetric_weight', 10, 2)->nullable();
            $table->decimal('chargeable_weight', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('box_details');
    }
};
