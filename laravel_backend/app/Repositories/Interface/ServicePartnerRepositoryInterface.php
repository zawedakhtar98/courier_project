<?php

namespace App\Repositories\Interface;

interface ServicePartnerRepositoryInterface
{
    public function create(array $data);
    public function update(array $data, int $id);
    public function delete(int $id);
    public function getAllServicePartner(int $perPage);
    public function findByName(string $name);
    public function findByStatus(string $status);
}
