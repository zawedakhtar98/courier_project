<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoxDetail extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'box_details';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'shipment_id',
        'no_of_box',
        'length',
        'width',
        'height',
        'actual_weight',
        'volumetric_weight',
        'chargeable_weight',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'length' => 'decimal:2',
            'width' => 'decimal:2',
            'height' => 'decimal:2',
            'actual_weight' => 'decimal:2',
            'volumetric_weight' => 'decimal:2',
            'chargeable_weight' => 'decimal:2',
        ];
    }

    /**
     * Get the shipment that owns the box detail.
     */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(ShipmentDetail::class, 'shipment_id');
    }
}
