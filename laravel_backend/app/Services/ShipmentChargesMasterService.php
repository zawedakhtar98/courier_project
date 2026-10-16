<?php

namespace App\Services;

use App\Models\ShipmentChargesMaster;
use App\Repositories\Interface\ShipmentChargesMasterRespositoryInterface;

class ShipmentChargesMasterService
{

    public function __construct(private ShipmentChargesMasterRespositoryInterface $shipmentChargesMasterRespository) {}
    public function addShipmentChargeMaster(array $data)
    {
        return $this->shipmentChargesMasterRespository->create($data);
    }
    public function deleteShipmentChargeMaster(int $id)
    {
        return $this->shipmentChargesMasterRespository->delete($id);
    }
    public function getAllShipmentChargeMaster()
    {
        return $this->shipmentChargesMasterRespository->getAll();
    }

    public function getActiveShipmentChargeMaster()
    {
        return ShipmentChargesMaster::where('status', 'active')->get();
    }
}
