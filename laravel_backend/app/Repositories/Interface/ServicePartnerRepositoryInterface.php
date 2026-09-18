<?php

namespace App\Repositories\Interface;

interface ServicePartnerRepositoryInterface
{
    public function create(array $data);
    public function update(array $data, int $id);
    public function delete(int $id);
    public function findByName(string $name);
    public function getAllServicePartner(int $perPage = 10);
    public function getAllServicePartnerWithZoneRates(int $perPage);
}
