<?php

namespace App\Repositories\Interface;

interface ZoneMasterRepositoryInterface
{
    public function getAllZone();
    public function create(array $data);
    public function update(array $data, int $id);
    public function deleteZone(int $id);
    public function findById(int $id);
}
