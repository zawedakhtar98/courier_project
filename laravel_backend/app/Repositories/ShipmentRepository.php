<?php

namespace App\Repositories;

use App\Models\BoxDetail;
use App\Models\InvoiceDetails;
use App\Models\InvoiceItemDetails;
use App\Models\ShipmentDetail;
use App\Repositories\Interface\ShipmentRepositoryInterface;

class ShipmentRepository implements ShipmentRepositoryInterface
{
    public function createShipment(array $data): ShipmentDetail
    {
        return ShipmentDetail::create($data);
    }

    public function createBoxes(int $shipmentId, array $boxes): array
    {
        $createdBoxes = [];
        foreach ($boxes as $box) {
            $createdBoxes[] = BoxDetail::create([
                'shipment_id'        => $shipmentId,
                'no_of_box'          => isset($box['boxNo']) ? (int)$box['boxNo'] : 1,
                'length'             => isset($box['length']) && $box['length'] !== '' ? (float)$box['length'] : 0,
                'width'              => isset($box['width']) && $box['width'] !== '' ? (float)$box['width'] : 0,
                'height'             => isset($box['height']) && $box['height'] !== '' ? (float)$box['height'] : 0,
                'actual_weight'      => isset($box['actWt']) && $box['actWt'] !== '' ? (float)$box['actWt'] : 0,
                'volumetric_weight'  => isset($box['volWt']) && $box['volWt'] !== '' ? (float)$box['volWt'] : 0,
                'chargeable_weight'  => isset($box['chgWt']) && $box['chgWt'] !== '' ? (float)$box['chgWt'] : 0,
            ]);
        }
        return $createdBoxes;
    }

    public function createInvoice(int $shipmentId, array $invoiceData, array $items): InvoiceDetails
    {
        $invoice = InvoiceDetails::create([
            'shipment_id'          => $shipmentId,
            'invoice_no'           => $invoiceData['invoiceNo'] ?? null,
            'invoice_type'         => $invoiceData['invoiceType'] ?? 'CSB 4',
            'invoice_date'         => !empty($invoiceData['invoiceDate']) ? $invoiceData['invoiceDate'] : date('Y-m-d'),
            'incoterms'            => $invoiceData['Incoterms'] ?? 'DDU',
            'notes'                => $invoiceData['invoiceNote'] ?? 'Gift',
            'currency'             => $invoiceData['currency'] ?? 'INR',
            'total_invoice_amount' => isset($invoiceData['invoiceTotalVal']) ? (float)$invoiceData['invoiceTotalVal'] : 0,
        ]);

        foreach ($items as $item) {
            InvoiceItemDetails::create([
                'invoice_id'   => $invoice->id,
                'box_no'       => isset($item['boxNo']) ? (int)$item['boxNo'] : 1,
                'description'  => $item['description'] ?? '',
                'hsn_code'     => !empty($item['hsnCode']) ? (int)$item['hsnCode'] : null,
                'unit_type'    => $item['unit'] ?? 'PCS',
                'quantity'     => isset($item['qty']) ? (int)$item['qty'] : 1,
                'unit_price'   => isset($item['rate']) ? (float)$item['rate'] : 0,
                'total_price'  => isset($item['amount']) ? (float)$item['amount'] : 0,
            ]);
        }

        return $invoice->load('items');
    }

    public function findById(int $id): ?ShipmentDetail
    {
        return ShipmentDetail::with(['boxes', 'invoice.items', 'servicePartner', 'user', 'destinationCountry'])->find($id);
    }

    public function findByShipmentNo(string $shipmentNo): ?ShipmentDetail
    {
        return ShipmentDetail::with(['boxes', 'invoice.items', 'servicePartner', 'user', 'destinationCountry'])
            ->where('shipment_no', $shipmentNo)
            ->first();
    }

    public function getAll(int $perPage = 15, int $page = 1, array $filters = [])
    {
        $query = ShipmentDetail::with(['boxes', 'invoice.items', 'servicePartner', 'destinationCountry', 'user']);

        // Search filter (AWB, Forwarding No, Consignee, Shipper, Origin Hub, etc.)
        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('shipment_no', 'LIKE', "%{$search}%")
                    ->orWhere('reference_no', 'LIKE', "%{$search}%")
                    ->orWhere('sender_name', 'LIKE', "%{$search}%")
                    ->orWhere('sender_company_name', 'LIKE', "%{$search}%")
                    ->orWhere('consignee_name', 'LIKE', "%{$search}%")
                    ->orWhere('consignee_company_name', 'LIKE', "%{$search}%")
                    ->orWhere('sender_city', 'LIKE', "%{$search}%")
                    ->orWhere('consignee_city', 'LIKE', "%{$search}%")
                    ->orWhereHas('destinationCountry', function ($cq) use ($search) {
                        $cq->where('name', 'LIKE', "%{$search}%");
                    })
                    ->orWhereHas('servicePartner', function ($sq) use ($search) {
                        $sq->where('name', 'LIKE', "%{$search}%");
                    });
            });
        }

        // Service partner filter
        if (!empty($filters['service']) && $filters['service'] !== 'All') {
            $service = $filters['service'];
            $query->where(function ($q) use ($service) {
                if (is_numeric($service)) {
                    $q->where('service_partner_id', $service);
                } else {
                    $q->whereHas('servicePartner', function ($sq) use ($service) {
                        $sq->where('name', 'LIKE', "%{$service}%");
                    });
                }
            });
        }

        // Status filter
        if (!empty($filters['status']) && $filters['status'] !== 'All') {
            $query->where('status', $filters['status']);
        }

        return $query->latest()->paginate($perPage, ['*'], 'page', $page);
    }

    public function getDashboardStats(array $filters = []): array
    {
        $query = ShipmentDetail::query();

        // Timeframe / Date filters
        $timeframe = $filters['timeframe'] ?? 'all';
        $fromDate = $filters['from_date'] ?? null;
        $toDate = $filters['to_date'] ?? null;

        if ($timeframe === 'today') {
            $query->whereDate('booking_date', date('Y-m-d'));
        } elseif ($timeframe === 'yesterday') {
            $query->whereDate('booking_date', date('Y-m-d', strtotime('-1 day')));
        } elseif ($timeframe === 'this_week') {
            $monday = date('Y-m-d', strtotime('monday this week'));
            $sunday = date('Y-m-d', strtotime('sunday this week'));
            $query->whereBetween('booking_date', [$monday, $sunday]);
        } elseif ($timeframe === 'this_month') {
            $query->whereYear('booking_date', date('Y'))->whereMonth('booking_date', date('m'));
        } elseif ($timeframe === 'this_year') {
            $query->whereYear('booking_date', date('Y'));
        } elseif (!empty($fromDate) && !empty($toDate)) {
            $query->whereBetween('booking_date', [$fromDate, $toDate]);
        } elseif (!empty($fromDate)) {
            $query->whereDate('booking_date', '>=', $fromDate);
        } elseif (!empty($toDate)) {
            $query->whereDate('booking_date', '<=', $toDate);
        }

        // Status counts
        $totalShipments = (clone $query)->count();
        $delivered = (clone $query)->where('status', 'delivered')->count();
        $inTransit = (clone $query)->whereIn('status', ['in_transit', 'pickup_in_progress', 'ready_to_ship'])->count();
        $cancelled = (clone $query)->whereIn('status', ['cancel', 'cancelled'])->count();
        $created = (clone $query)->where('status', 'created')->count();

        // Weight, boxes and invoice totals
        $totalWeight = (float)(clone $query)->sum('total_chargeable_weight');
        $totalBoxes = (int)(clone $query)->sum('total_box');
        $totalInvoiceAmount = (float)(clone $query)->sum('total_invoice_value');

        // Recent shipments with relations
        $recentShipments = (clone $query)
            ->with(['destinationCountry', 'servicePartner', 'boxes', 'invoice'])
            ->latest('id')
            ->limit(8)
            ->get();

        return [
            'summary' => [
                'total_shipments'      => $totalShipments,
                'delivered'            => $delivered,
                'in_transit'           => $inTransit,
                'cancelled'            => $cancelled,
                'created'              => $created,
                'total_weight'         => round($totalWeight, 2),
                'total_boxes'          => $totalBoxes,
                'total_invoice_amount' => round($totalInvoiceAmount, 2),
            ],
            'timeframe'        => $timeframe,
            'from_date'        => $fromDate,
            'to_date'          => $toDate,
            'recent_shipments' => $recentShipments,
        ];
    }

    public function delete(int $id): bool
    {
        $shipment = ShipmentDetail::find($id);
        if ($shipment) {
            return (bool)$shipment->delete();
        }
        return false;
    }
}
