<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItemDetails extends Model
{
    use HasFactory;

    protected $table = 'invoice_item_details';

    protected $fillable = [
        'invoice_id',
        'box_no',
        'description',
        'hsn_code',
        'unit_type',
        'quantity',
        'unit_price',
        'total_price',
    ];

    protected function casts(): array
    {
        return [
            'quantity'    => 'integer',
            'unit_price'  => 'decimal:2',
            'total_price' => 'decimal:2',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(InvoiceDetails::class, 'invoice_id');
    }
}
