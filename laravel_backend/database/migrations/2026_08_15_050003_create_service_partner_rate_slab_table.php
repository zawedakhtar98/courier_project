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
        Schema::create('service_partner_rate_slab', function (Blueprint $table) {
            $table->id();
            $table->integer('service_partner_id');
            $table->integer('zone_id');
            $table->enum('package_type', ['doc', 'nondoc']);
            $table->decimal('weight_from', 10, 2);
            $table->decimal('weight_to', 10, 2);
            $table->decimal('rate', 10, 2);
            $table->enum('rate_type', ['per_kg', 'flat']);
            $table->string('currency', 10)->default('INR');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_partner_rate_slab');
    }
};
