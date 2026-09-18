<?php

namespace App\Repositories;

use App\Models\ServicePartnerZoneRate;
use App\Repositories\Interface\ServicePartnerZoneRateRepositoryInterface;

class ServicePartnerZoneRateRepository implements ServicePartnerZoneRateRepositoryInterface
{

    public function create(array $data)
    {
        return ServicePartnerZoneRate::insert($data);
    }

    public function update(int $id, array $data)
    {
        $rate = ServicePartnerZoneRate::findOrFail($id);
        return $rate->update($data);
    }

    public function delete(int $id)
    {
        $rate = ServicePartnerZoneRate::findOrFail($id);
        return $rate->delete();
    }

    public function getServicePartnerZoneRate(int $servicePartnerId) {}

    public function getZoneServicePartnerRate(int $zoneId) {}

    public function getAllServicePartnerZoneRates(int $per_page = 20, int $page = 1, array $filter = [])
    {
        return ServicePartnerZoneRate::with('servicePartner', 'zone')->paginate($per_page);
    }
}
