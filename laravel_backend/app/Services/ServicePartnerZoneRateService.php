<?php

namespace App\Services;

use App\Repositories\Interface\ServicePartnerZoneRateRepositoryInterface;

class ServicePartnerZoneRateService
{
    /**
     * Create a new class instance`.
     */
    public function __construct(protected ServicePartnerZoneRateRepositoryInterface $servicePartnerZoneRateRepository)
    {
        //
    }

    public function create(array $data)
    {
        return $this->servicePartnerZoneRateRepository->create($data);
    }

    public function update(int $id, array $data)
    {
        return $this->servicePartnerZoneRateRepository->update($id, $data);
    }

    public function delete(int $id)
    {
        return $this->servicePartnerZoneRateRepository->delete($id);
    }

    public function getAllServicePartnerZoneRates(int $per_page = 20)
    {
        return $this->servicePartnerZoneRateRepository->getAllServicePartnerZoneRates($per_page);
    }
}
