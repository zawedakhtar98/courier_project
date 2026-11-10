<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InvoiceDetails extends Model
{
    use HasFactory;

    protected $table = 'invoice_details';

    protected $fillable = [
        'shipment_id',
        'invoice_no',
        'invoice_type',
        'invoice_date',
        'incoterms',
        'notes',
        'currency',
        'total_invoice_amount',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date'         => 'date',
            'total_invoice_amount' => 'decimal:2',
        ];
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(ShipmentDetail::class, 'shipment_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItemDetails::class, 'invoice_id');
    }
}
