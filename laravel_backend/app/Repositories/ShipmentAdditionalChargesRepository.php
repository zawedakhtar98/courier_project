<?php

namespace App\Repositories;

use App\Models\ShipmentAdditionalCharges;
use App\Repositories\Interface\ShipmentAdditionalChargesRepositoryInterface;

class ShipmentAdditionalChargesRepository implements ShipmentAdditionalChargesRepositoryInterface
{
    public function create(array $data)
    {
        return ShipmentAdditionalCharges::create($data);
    }

    public function update(array $data, int $id)
    {
        $charge = ShipmentAdditionalCharges::find($id);
        if ($charge) {
            $charge->update($data);
        }
        return $charge;
    }

    public function delete(int $id)
    {
        $charge = ShipmentAdditionalCharges::find($id);
        if ($charge) {
            $charge->delete();
        }
        return $charge;
    }

    public function findById(int $id)
    {
        return ShipmentAdditionalCharges::with(['servicePartner', 'shipmentChargeType'])->find($id);
    }

    public function getAll(int $perPage = 100)
    {
        return ShipmentAdditionalCharges::with(['servicePartner', 'shipmentChargeType'])
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'desc')
            ->paginate($perPage);
    }

    public function getAllActive()
    {
        return ShipmentAdditionalCharges::with(['servicePartner', 'shipmentChargeType'])
            ->where('status', 'active')
            ->orderBy('sort_order', 'asc')
            ->get();
    }

    public function getByServicePartner(int $servicePartnerId)
    {
        return ShipmentAdditionalCharges::with('shipmentChargeType')
            ->where('service_partner_id', $servicePartnerId)
            ->where('status', 'active')
            ->orderBy('sort_order', 'asc')
            ->get();
    }

    public function findByPartnerAndChargeType(int $servicePartnerId, int $chargeTypeId)
    {
        return ShipmentAdditionalCharges::where('service_partner_id', $servicePartnerId)
            ->where('charge_type_id', $chargeTypeId)
            ->first();
    }

    public function getNextOrderValue()
    {
        return (ShipmentAdditionalCharges::latest()->first()->sort_order ?? 0) + 1;
    }
}
