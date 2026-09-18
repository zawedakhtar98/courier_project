<?php

namespace App\Repositories\Interface;

interface ServicePartnerZoneRateRepositoryInterface
{
    /**
     * Create a new class instance.
     */
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);
    public function getAllServicePartnerZoneRates(int $per_page = 20);
}
