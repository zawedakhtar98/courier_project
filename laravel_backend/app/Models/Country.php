<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'country';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'status',
        'short_name',
    ];

    /**
     * Get the service partner rate mappings for the country.
     */
    public function servicePartnerRates(): HasMany
    {
        return $this->hasMany(ServicePartnerCountryRate::class, 'country_id');
    }

    /**
     * Get the shipments destined for this country.
     */
    public function shipments(): HasMany
    {
        return $this->hasMany(ShipmentDetail::class, 'receiver_country_id');
    }
}
