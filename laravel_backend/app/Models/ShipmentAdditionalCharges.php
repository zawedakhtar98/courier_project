<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShipmentAdditionalCharges extends Model
{
    protected $table = 'shipment_additional_charges';
    protected $fillable = [
        'service_partner_zone_rate_id', // this id below is from service_partner_rate_slab table
        'charge_type_id', // this id below is from shipment_charges_master table
        'value', //value of the charge
        'status',
        'sort_order', //execution order for the calculation loop
    ];

    public function shipmentChargeType()
    {
        return $this->belongsTo(ShipmentChargesMaster::class, 'charge_type_id', 'id');
    }
}
