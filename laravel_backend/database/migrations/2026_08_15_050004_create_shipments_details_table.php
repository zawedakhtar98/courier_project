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
        Schema::create('shipments_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_partner_id')->nullable()->constrained('servicepartner')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('awb_number', 100)->nullable()->unique();

            // Sender Details
            $table->string('sender_name', 255)->nullable();
            $table->string('sender_company_name', 255)->nullable();
            $table->string('sender_contact_person_name', 255)->nullable();
            $table->string('sender_address_line_1', 255)->nullable();
            $table->string('sender_address_line_2', 255)->nullable();
            $table->string('sender_address_line_3', 255)->nullable();
            $table->string('sender_city', 100)->nullable();
            $table->string('sender_state', 100)->nullable();
            $table->string('sender_pincode', 50)->nullable();
            $table->enum('sender_type', ['individual', 'business'])->nullable();
            $table->enum('sender_kyc_type', ['aadhaar', 'passport', 'gstin'])->nullable();
            $table->string('sender_kyc_number', 255)->nullable();
            $table->string('sender_telephone', 50)->nullable();
            $table->string('sender_email', 255)->nullable();

            // Receiver Details
            $table->string('receiver_name', 255)->nullable();
            $table->string('receiver_company_name', 255)->nullable();
            $table->string('receiver_contact_person_name', 255)->nullable();
            $table->string('receiver_address_line_1', 255)->nullable();
            $table->string('receiver_address_line_2', 255)->nullable();
            $table->string('receiver_address_line_3', 255)->nullable();
            $table->string('receiver_city', 100)->nullable();
            $table->string('receiver_state', 100)->nullable();
            $table->string('receiver_pincode', 50)->nullable();
            $table->enum('receiver_type', ['individual', 'business'])->nullable();
            $table->string('receiver_vat_tax_id', 255)->nullable();
            $table->string('receiver_telephone', 50)->nullable();
            $table->string('receiver_email', 255)->nullable();
            $table->foreignId('receiver_country_id')->nullable()->constrained('country')->nullOnDelete();

            // Shipment Metadata
            $table->enum('payment_status', ['paid', 'pending', 'cancel'])->default('pending');
            $table->enum('status', ['draft', 'pending', 'cancel', 'intransit', 'delivered'])->default('draft');
            $table->enum('created_by', ['employee', 'admin', 'customer'])->nullable();
            $table->string('goods_type', 100)->nullable();
            $table->decimal('actual_weight', 10, 2)->nullable();
            $table->decimal('chargeable_weight', 10, 2)->nullable();
            $table->decimal('shipment_total_cost', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipments_details');
    }
};
