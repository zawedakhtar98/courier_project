<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServicePartnerCountryRate extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'service_partner_country_rate';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'service_partner_id',
        'country_id',
        'service_code',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the service partner that owns this rate mapping.
     */
    public function servicePartner(): BelongsTo
    {
        return $this->belongsTo(ServicePartner::class, 'service_partner_id');
    }

    /**
     * Get the country associated with this rate mapping.
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    /**
     * Get the rate slabs for this mapping.
     */
    public function rateSlabs(): HasMany
    {
        return $this->hasMany(ServicePartnerRateSlab::class, 'mapping_id');
    }
}
