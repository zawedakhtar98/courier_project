<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ShipmentDetail extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'shipment_details';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'shipment_no',
        'reference_no',
        'shipment_type',
        'origin',
        'destination',
        'booking_date',
        'shipment_date',
        'package_type',
        'service_partner_id',

        // Sender Details
        'sender_name',
        'sender_company_name',
        'sender_address_line_1',
        'sender_address_line_2',
        'sender_address_line_3',
        'sender_mobile',
        'sender_email',
        'sender_city',
        'sender_state',
        'sender_country',
        'sender_pincode',
        'sender_kyc_type',
        'sender_kyc_number',
        'sender_kyc_front',
        'sender_kyc_back',

        // Consignee Details
        'consignee_name',
        'consignee_company_name',
        'consignee_address_line_1',
        'consignee_address_line_2',
        'consignee_address_line_3',
        'consignee_city',
        'consignee_state',
        'consignee_country',
        'consignee_pincode',
        'consignee_mobile_no',
        'consignee_email',

        // Totals & Invoicing
        'total_box',
        'total_actual_weight',
        'total_chargeable_weight',
        'total_volume_weight',
        'total_invoice_value',
        'booking_amount',
        'payment_status',
        'status',
        'created_by',
        'shipment_generated_from',
        'payment_mode',
        'currency',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_actual_weight'     => 'decimal:2',
            'total_chargeable_weight' => 'decimal:2',
            'total_volume_weight'     => 'decimal:2',
            'total_invoice_value'     => 'decimal:2',
            'booking_amount'          => 'decimal:2',
            'booking_date'            => 'date',
            'shipment_date'           => 'date',
        ];
    }

    /**
     * Service Partner relationship.
     */
    public function servicePartner(): BelongsTo
    {
        return $this->belongsTo(ServicePartner::class, 'service_partner_id');
    }

    /**
     * User who created the shipment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Destination Country relationship.
     */
    public function destinationCountry(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'destination');
    }

    /**
     * Box details for the shipment.
     */
    public function boxDetails(): HasMany
    {
        return $this->hasMany(BoxDetail::class, 'shipment_id');
    }

    /**
     * Alias for boxDetails relation.
     */
    public function boxes(): HasMany
    {
        return $this->boxDetails();
    }

    /**
     * Invoice details for the shipment.
     */
    public function invoice(): HasOne
    {
        return $this->hasOne(InvoiceDetails::class, 'shipment_id');
    }
}
