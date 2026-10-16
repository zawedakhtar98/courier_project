<?php

namespace App\Repositories\Interface;

interface ShipmentAdditionalChargesRepositoryInterface
{
    public function create(array $data);
    public function update(array $data, int $id);
    public function delete(int $id);
    public function findById(int $id);
    public function getAll(int $perPage = 10);
    public function getAllActive();
    public function getNextOrderValue();
    public function getByServicePartner(int $servicePartnerId);
    public function findByPartnerAndChargeType(int $servicePartnerId, int $chargeTypeId);
}
