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
        Schema::create('invoice_item_details', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('invoice_id')->nullable();
            $table->integer('box_no')->nullable();
            $table->string('description')->nullable();
            $table->integer('hsn_code')->nullable();
            $table->string('unit_type', 20)->nullable();
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2)->nullable();
            $table->decimal('total_price', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_item_details');
    }
};
