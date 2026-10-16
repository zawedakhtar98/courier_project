<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShipmentAdditionalChargesResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'service_partner_id' => $this->service_partner_id,
            'charge_type_id' => $this->charge_type_id,
            'service_partner_name' => $this->servicePartner->name,
            'charges_type' => $this->shipmentChargeType->name,
            'charges_value' => $this->value,
            'calculation_method' => $this->shipmentChargeType->calculation_method,
            'calculation_basis' => $this->shipmentChargeType->calculation_basis,
            'code' => $this->shipmentChargeType->code,
            'description' => $this->shipmentChargeType->description,
            'sort_order' => $this->sort_order,
            'status' => $this->status,
            'created_at' => Carbon::parse($this->created_at)->format('d-m-Y'),
        ];
    }
}
