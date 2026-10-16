<?php

namespace App\Services;

use App\Repositories\Interface\ShipmentAdditionalChargesRepositoryInterface;

class ShipmentAdditionalChargeService
{
    /**
     * Create a new class instance.
     */
    public function __construct(protected ShipmentAdditionalChargesRepositoryInterface $repository) {}

    public function create(array $data)
    {
        return $this->repository->create($data);
    }

    public function update(array $data, int $id)
    {
        return $this->repository->update($data, $id);
    }

    public function delete(int $id)
    {
        return $this->repository->delete($id);
    }

    public function findById(int $id)
    {
        return $this->repository->findById($id);
    }

    public function getAll(int $perPage = 100)
    {
        return $this->repository->getAll($perPage);
    }

    public function getAllActive()
    {
        return $this->repository->getAllActive();
    }

    public function getByServicePartner(int $servicePartnerId)
    {
        return $this->repository->getByServicePartner($servicePartnerId);
    }

    public function getNextSortOrder()
    {
        return $this->repository->getNextOrderValue();
    }
}
