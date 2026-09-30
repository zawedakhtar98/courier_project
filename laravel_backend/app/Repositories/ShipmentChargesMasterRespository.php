<?php

namespace App\Repositories;

use App\Models\ShipmentChargesMaster;
use App\Repositories\Interface\ShipmentChargesMasterRespositoryInterface;

class ShipmentChargesMasterRespository implements ShipmentChargesMasterRespositoryInterface
{
    public function create(array $data)
    {
        return ShipmentChargesMaster::create($data);
    }

    public function findById(int $id)
    {
        return ShipmentChargesMaster::find($id);
    }

    public function update(array $data, int $id)
    {
        return ShipmentChargesMaster::where('id', $id)->update($data);
    }

    public function delete(int $id)
    {
        return ShipmentChargesMaster::destroy($id);
    }

    public function getAll()
    {
        return ShipmentChargesMaster::all();
    }

    public function getAllActive()
    {
        return ShipmentChargesMaster::where('status', 'active')->get();
    }
}
