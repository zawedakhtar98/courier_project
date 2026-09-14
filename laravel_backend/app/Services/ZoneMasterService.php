<?php

namespace App\Services;

use App\Repositories\ZoneMasterRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class ZoneMasterService
{
    /**
     * Create a new class instance.
     */
    public function __construct(protected ZoneMasterRepository $zoneMasterRepository)
    {
        $this->zoneMasterRepository = $zoneMasterRepository;
    }

    public function getAllZone()
    {
        return $this->zoneMasterRepository->getAllZone();
    }

    public function create(array $data)
    {
        return $this->zoneMasterRepository->create($data);
    }

    public function update(array $data, int $id)
    {
        return $this->zoneMasterRepository->update($data, $id);
    }

    public function deleteZone(int $id)
    {
        return $this->zoneMasterRepository->deleteZone($id);
    }

    public function findById($id)
    {
        return $this->zoneMasterRepository->findById($id);
    }
}
