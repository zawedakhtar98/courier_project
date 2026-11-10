<?php

namespace App\Repositories\Interface;

use App\Models\ShipmentDetail;
use App\Models\InvoiceDetails;

interface ShipmentRepositoryInterface
{
    public function createShipment(array $data): ShipmentDetail;

    public function createBoxes(int $shipmentId, array $boxes): array;

    public function createInvoice(int $shipmentId, array $invoiceData, array $items): InvoiceDetails;

    public function findById(int $id): ?ShipmentDetail;

    public function findByShipmentNo(string $shipmentNo): ?ShipmentDetail;

    public function getAll(int $perPage = 15, int $page = 1, array $filters = []);

    public function getDashboardStats(array $filters = []): array;

    public function delete(int $id): bool;
}
