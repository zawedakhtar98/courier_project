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
        Schema::create('invoice_details', function (Blueprint $table) {
            $table->id();
            $table->integer('shipment_id')->nullable();
            $table->string('invoice_no')->nullable();
            $table->string('invoice_type', 100)->nullable();
            $table->date('invoice_date')->nullable();
            $table->string('incoterms', 100)->nullable();
            $table->string('notes', 30)->nullable()->comment('gift or sample etc');
            $table->string('currency', 10)->nullable();
            $table->decimal('total_invoice_amount', 12, 2)->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_details');
    }
};
