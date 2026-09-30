<?php

namespace App\Repositories\Interface;

interface ShipmentChargesMasterRespositoryInterface
{
    public function create(array $data);
    public function update(array $data, int $id);
    public function delete(int $id);
    public function getAll();
    public function getAllActive();
    public function findById(int $id);
}
