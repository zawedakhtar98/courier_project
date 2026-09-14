<?php

namespace App\Repositories;

use App\Models\ZoneMaster;

class ZoneMasterRepository
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

    public function findById($id)
    {
        return ZoneMaster::find($id);
    }
}
