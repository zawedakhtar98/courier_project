<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServicePartner extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'servicepartner';

    /**
     * The name of the "created at" column.
     *
     * @var string|null
     */
    const CREATED_AT = 'created_date';

    /**
     * The name of the "updated at" column.
     *
     * @var string|null
     */
    const UPDATED_AT = 'updated_date';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'service_code',
        'status',
        'created_date',
        'updated_date',
    ];

    /**
     * Get the shipments associated with the service partner.
     */

    public function zoneRates(): HasMany
    {
        return $this->hasMany(ServicePartnerZoneRate::class, 'service_partner_id');
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(ShipmentDetail::class, 'service_partner_id');
    }
}
