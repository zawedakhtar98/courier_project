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
            $table->foreignId('user_id')->nullable()->comment('shipment created by')->constrained('users')->nullOnDelete();
            $table->string('shipment_no', 100)->nullable()->unique()->comment('shipment no consider as AWB no');
            $table->string('reference_no', 100)->nullable();
            $table->enum('shipment_type', ['import', 'export'])->nullable()->default('export');
            $table->integer('origin')->nullable()->comment('origin country id');
            $table->integer('destination')->nullable()->comment('destination country id');
            $table->date('booking_date')->nullable()->default(date('Y-m-d'));
            $table->date('shipment_date')->nullable()->comment('pickup date');
            $table->string('package_type', 30)->nullable();
            $table->foreignId('service_partner_id')->nullable()->constrained('servicepartner')->nullOnDelete();
            // Sender Details
            $table->string('sender_name', 255)->nullable();
            $table->string('sender_company_name', 255)->nullable();
            $table->string('sender_address_line_1', 255)->nullable();
            $table->string('sender_address_line_2', 255)->nullable();
            $table->string('sender_address_line_3', 255)->nullable();
            $table->string('sender_mobile', 50)->nullable();
            $table->string('sender_email', 255)->nullable();
            $table->string('sender_city', 100)->nullable();
            $table->string('sender_state', 100)->nullable();
            $table->string('sender_pincode', 50)->nullable();
            $table->string('sender_kyc_type', 50)->nullable();
            $table->string('sender_kyc_number', 100)->nullable();
            $table->string('sender_kyc_front', 255)->nullable();
            $table->string('sender_kyc_back', 255)->nullable();

            // Receiver Details
            $table->string('consignee_name', 255)->nullable();
            $table->string('consignee_company_name', 255)->nullable();
            $table->string('consignee_address_line_1', 255)->nullable();
            $table->string('consignee_address_line_2', 255)->nullable();
            $table->string('consignee_address_line_3', 255)->nullable();
            $table->string('consignee_city', 100)->nullable();
            $table->string('consignee_state', 100)->nullable();
            $table->string('consignee_pincode', 50)->nullable();
            $table->string('consignee_mobile_no', 50)->nullable();
            $table->string('consignee_email', 255)->nullable();

            //invoicing details
            $table->integer('total_box')->nullable();
            $table->decimal('total_actual_weight', 10, 2)->nullable();
            $table->decimal('total_chargeable_weight', 10, 2)->nullable();
            $table->decimal('total_volume_weight', 10, 2)->nullable();
            $table->decimal('total_invoice_value', 2)->nullable();
            $table->decimal('booking_amount', 10, 2)->nullable();
            $table->enum('payment_status', ['paid', 'pending', 'cancel', 'partially_paid'])->default('pending');
            $table->enum('status', ['created', 'pickup_in_progress', 'ready_to_ship', 'in_transit', 'delivered', 'cancel'])->default('created');
            $table->enum('created_by', ['employee', 'admin', 'customer'])->nullable();
            $table->enum('shipment_generated_from', ['system', 'API'])->default('system')->nullable();
            $table->enum('payment_mode', ['online', 'cash', 'cheque', 'cod', 'neft', 'rtgs', 'upi', 'card', 'wallet'])->nullable();
            $table->string('currency', 10)->default('INR')->nullable();
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
