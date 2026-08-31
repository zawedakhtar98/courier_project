<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceLineItem extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'invoice_line_items';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'invoice_id',
        'box_number',
        'description',
        'hsn_code',
        'hts_code',
        'unit',
        'qty',
        'rate',
        'amount',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'qty' => 'integer',
            'rate' => 'decimal:2',
            'amount' => 'decimal:2',
        ];
    }

    /**
     * Get the customs invoice that owns the line item.
     */
    public function customsInvoice(): BelongsTo
    {
        return $this->belongsTo(CustomsInvoice::class, 'invoice_id');
    }
}
