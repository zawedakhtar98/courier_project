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
    protected $table = 'shipments_details';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'service_partner_id',
        'user_id',
        'awb_number',
        'sender_name',
        'sender_company_name',
        'sender_contact_person_name',
        'sender_address_line_1',
        'sender_address_line_2',
        'sender_address_line_3',
        'sender_city',
        'sender_state',
        'sender_pincode',
        'sender_type',
        'sender_kyc_type',
        'sender_kyc_number',
        'sender_telephone',
        'sender_email',
        'receiver_name',
        'receiver_company_name',
        'receiver_contact_person_name',
        'receiver_address_line_1',
        'receiver_address_line_2',
        'receiver_address_line_3',
        'receiver_city',
        'receiver_state',
        'receiver_pincode',
        'receiver_type',
        'receiver_vat_tax_id',
        'receiver_telephone',
        'receiver_email',
        'receiver_country_id',
        'payment_status',
        'status',
        'created_by',
        'goods_type',
        'actual_weight',
        'chargeable_weight',
        'shipment_total_cost',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'actual_weight' => 'decimal:2',
            'chargeable_weight' => 'decimal:2',
            'shipment_total_cost' => 'decimal:2',
        ];
    }

    /**
     * Get the service partner for the shipment.
     */
    public function servicePartner(): BelongsTo
    {
        return $this->belongsTo(ServicePartner::class, 'service_partner_id');
    }

    /**
     * Get the user who owns or created the shipment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the receiver country for the shipment.
     */
    public function receiverCountry(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'receiver_country_id');
    }

    /**
     * Get the box details for the shipment.
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
     * Get the customs invoices for the shipment.
     */
    public function customsInvoices(): HasMany
    {
        return $this->hasMany(CustomsInvoice::class, 'shipment_id');
    }

    /**
     * Get the primary customs invoice for the shipment.
     */
    public function customsInvoice(): HasOne
    {
        return $this->hasOne(CustomsInvoice::class, 'shipment_id');
    }
}
