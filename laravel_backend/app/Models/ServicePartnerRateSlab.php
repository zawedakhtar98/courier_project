<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServicePartnerRateSlab extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'service_partner_rate_slab';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'mapping_id',
        'package_type',
        'weight_from',
        'weight_to',
        'rate',
        'rate_type',
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
            'weight_from' => 'decimal:2',
            'weight_to' => 'decimal:2',
            'rate' => 'decimal:2',
        ];
    }

    /**
     * Get the country rate mapping that owns the rate slab.
     */
    public function countryRate(): BelongsTo
    {
        return $this->belongsTo(ServicePartnerCountryRate::class, 'mapping_id');
    }

    /**
     * Alias for countryRate relation.
     */
    public function mapping(): BelongsTo
    {
        return $this->countryRate();
    }
}
