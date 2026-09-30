<?php

namespace App\Services;

use App\Models\ShipmentChargesMaster;
use App\Repositories\Interface\ShipmentChargesMasterRespositoryInterface;

class ShipmentChargesMasterService
{

    public function __construct(private ShipmentChargesMasterRespositoryInterface $shipmentChargesMasterRespository) {}
    public function addNew(array $data)
    {
        return $this->shipmentChargesMasterRespository->create($data);
    }
    public function delete(int $id)
    {
        return $this->shipmentChargesMasterRespository->delete($id);
    }
    public function getAllMasterCharges()
    {
        return $this->shipmentChargesMasterRespository->getAll();
    }

    public function getAllActiveMasterCharges()
    {
        return ShipmentChargesMaster::where('status', 'active')->get();
    }
}
