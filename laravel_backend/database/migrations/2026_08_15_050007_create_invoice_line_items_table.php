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
        Schema::create('invoice_line_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('customs_invoice')->cascadeOnDelete();
            $table->integer('box_number')->nullable();
            $table->text('description')->nullable();
            $table->string('hsn_code', 50)->nullable();
            $table->string('hts_code', 50)->nullable();
            $table->string('unit', 50)->nullable();
            $table->integer('qty')->nullable();
            $table->decimal('rate', 10, 2)->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_line_items');
    }
};
