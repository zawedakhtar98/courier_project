<?php

namespace App\Services;

use App\Repositories\Interface\ServicePartnerRepositoryInterface;

class ServicePartnerServices
{
    /**
     * Create a new class instance.
     */
    public function __construct(protected ServicePartnerRepositoryInterface $servicePartnerRepository)
    {
        $this->servicePartnerRepository = $servicePartnerRepository;
    }

    public function createServicePartner(array $data)
    {
        return $this->servicePartnerRepository->create($data);
    }

    public function updateServicePartner(array $data, int $id)
    {
        return $this->servicePartnerRepository->update($data, $id);
    }

    public function deleteServicePartner(int $id)
    {
        return $this->servicePartnerRepository->delete($id);
    }

    public function getAllServicePartner(int $perPage)
    {
        return $this->servicePartnerRepository->getAllServicePartner($perPage);
    }

    public function findServicePartnerByName(string $name)
    {
        return $this->servicePartnerRepository->findByName($name);
    }

    public function findServicePartnerByStatus(string $status)
    {
        return $this->servicePartnerRepository->findByStatus($status);
    }
}
