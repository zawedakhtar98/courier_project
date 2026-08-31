<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomsInvoice extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'customs_invoice';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'shipment_id',
        'csb_type',
        'invoice_number',
        'invoice_date',
        'term_of_trade',
        'reason_for_export',
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
            'invoice_date' => 'date',
        ];
    }

    /**
     * Get the shipment associated with the customs invoice.
     */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(ShipmentDetail::class, 'shipment_id');
    }

    /**
     * Get the line items for the customs invoice.
     */
    public function lineItems(): HasMany
    {
        return $this->hasMany(InvoiceLineItem::class, 'invoice_id');
    }
}
