<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShipmentChargesMaster extends Model
{
    //
    protected $table = 'shipment_charges_master';
    protected $fillable = [
        'name',
        'code',
        'description',
        'calculation_method',
        'calculation_basis',
        'sort_order',
        'status',
    ];

    public function zoneWiseCharge()
    {
        return $this->hasMany(ServicePartnerZoneWiseCharges::class, 'charge_type_id', 'id');
    }
}
