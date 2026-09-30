<?php

namespace App\Repositories;

use App\Models\ZoneMaster;
use App\Repositories\Interface\ZoneMasterRepositoryInterface;

class ZoneMasterRepository implements ZoneMasterRepositoryInterface
{

    public function getAllZone()
    {
        return ZoneMaster::with('mapCountries')->get();
    }

    public function create(array $data)
    {
        return ZoneMaster::create($data);
    }

    public function update(array $data, int $id)
    {
        return ZoneMaster::where('id', $id)->update($data);
    }

    public function deleteZone(int $id)
    {
        return ZoneMaster::destroy($id);
    }

    public function findById(int $id)
    {
        return ZoneMaster::find($id);
    }
}
