<?php

namespace App\Services;

use App\Models\ShipmentDetail;
use App\Repositories\Interface\ShipmentRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ShipmentService
{
    public function __construct(protected ShipmentRepositoryInterface $shipmentRepository) {}

    /**
     * Book a new shipment with boxes, customs invoice, and KYC documents in a single atomic transaction.
     */
    public function bookShipment(array $data, array $files, ?int $userId = null): ShipmentDetail
    {
        // 1. Generate unique AWB / Shipment Number (e.g. EXP261108ABCD)
        $shipmentNo = $this->generateUniqueShipmentNo();

        // 2. Parse nested JSON payloads if sent as string via FormData
        $boxes = is_string($data['boxes'] ?? null) ? json_decode($data['boxes'], true) : ($data['boxes'] ?? []);
        $invoiceMeta = is_string($data['invoice_meta'] ?? null) ? json_decode($data['invoice_meta'], true) : ($data['invoice_meta'] ?? []);
        $invoiceItems = is_string($data['invoice_items'] ?? null) ? json_decode($data['invoice_items'], true) : ($data['invoice_items'] ?? []);

        // 3. Handle KYC File Uploads
        $uploadedPaths = [];
        $kycFrontPath = null;
        $kycBackPath = null;

        try {
            if (isset($files['sender_kyc_front']) && $files['sender_kyc_front']->isValid()) {
                $kycFrontPath = $files['sender_kyc_front']->store('kyc_documents', 'public');
                $uploadedPaths[] = $kycFrontPath;
            }
            if (isset($files['sender_kyc_back']) && $files['sender_kyc_back']->isValid()) {
                $kycBackPath = $files['sender_kyc_back']->store('kyc_documents', 'public');
                $uploadedPaths[] = $kycBackPath;
            }

            // 4. Calculate Summary Metrics from boxes & invoice items
            $totalActualWeight = 0;
            $totalVolWeight = 0;
            $totalChargeableWeight = 0;
            foreach ($boxes as $b) {
                $totalActualWeight += isset($b['actWt']) ? (float)$b['actWt'] : 0;
                $totalVolWeight += isset($b['volWt']) ? (float)$b['volWt'] : 0;
                $totalChargeableWeight += isset($b['chgWt']) ? (float)$b['chgWt'] : 0;
            }

            $totalInvoiceVal = isset($invoiceMeta['invoiceTotalVal']) ? (float)$invoiceMeta['invoiceTotalVal'] : 0;

            // 5. Execute DB Transaction
            return DB::transaction(function () use (
                $data,
                $userId,
                $shipmentNo,
                $kycFrontPath,
                $kycBackPath,
                $boxes,
                $invoiceMeta,
                $invoiceItems,
                $totalActualWeight,
                $totalVolWeight,
                $totalChargeableWeight,
                $totalInvoiceVal
            ) {
                // A. Prepare Shipment Header Data
                $shipmentPayload = [
                    'user_id'                 => $userId,
                    'shipment_no'             => $shipmentNo,
                    'reference_no'            => $data['reference_no'] ?? null,
                    'shipment_type'           => $data['shipment_type'] ?? 'export',
                    'origin'                  => $data['origin'] ?? 1,
                    'destination'             => $data['destination'] ?? null,
                    'booking_date'            => date('Y-m-d'),
                    'shipment_date'           => $data['shipment_date'] ?? date('Y-m-d'),
                    'package_type'            => $data['package_type'] ?? 'NONDOC',
                    'service_partner_id'      => $data['service_partner_id'] ?? null,

                    // Sender
                    'sender_name'             => $data['sender_name'] ?? '',
                    'sender_company_name'     => $data['sender_company_name'] ?? '',
                    'sender_address_line_1'   => $data['sender_address_line_1'] ?? '',
                    'sender_address_line_2'   => $data['sender_address_line_2'] ?? null,
                    'sender_address_line_3'   => $data['sender_address_line_3'] ?? null,
                    'sender_mobile'           => $data['sender_mobile'] ?? '',
                    'sender_email'            => $data['sender_email'] ?? '',
                    'sender_city'             => $data['sender_city'] ?? '',
                    'sender_state'            => $data['sender_state'] ?? '',
                    'sender_country'          => $data['sender_country'] ?? 'India',
                    'sender_pincode'          => $data['sender_pincode'] ?? '',
                    'sender_kyc_type'         => $data['sender_kyc_type'] ?? '',
                    'sender_kyc_number'       => $data['sender_kyc_number'] ?? '',
                    'sender_kyc_front'        => $kycFrontPath,
                    'sender_kyc_back'         => $kycBackPath,

                    // Consignee
                    'consignee_name'          => $data['consignee_name'] ?? '',
                    'consignee_company_name'  => $data['consignee_company_name'] ?? '',
                    'consignee_address_line_1'=> $data['consignee_address_line_1'] ?? '',
                    'consignee_address_line_2'=> $data['consignee_address_line_2'] ?? null,
                    'consignee_address_line_3'=> $data['consignee_address_line_3'] ?? null,
                    'consignee_city'          => $data['consignee_city'] ?? '',
                    'consignee_state'         => $data['consignee_state'] ?? '',
                    'consignee_country'       => $data['consignee_country'] ?? null,
                    'consignee_pincode'       => $data['consignee_pincode'] ?? '',
                    'consignee_mobile_no'     => $data['consignee_mobile_no'] ?? '',
                    'consignee_email'         => $data['consignee_email'] ?? '',

                    // Totals
                    'total_box'               => count($boxes),
                    'total_actual_weight'     => round($totalActualWeight, 2),
                    'total_chargeable_weight' => round($totalChargeableWeight, 2),
                    'total_volume_weight'     => round($totalVolWeight, 2),
                    'total_invoice_value'     => round($totalInvoiceVal, 2),
                    'booking_amount'          => isset($data['booking_amount']) ? (float)$data['booking_amount'] : 0,
                    'payment_status'          => 'pending',
                    'status'                  => 'created',
                    'created_by'              => 'admin',
                    'shipment_generated_from' => 'system',
                    'currency'                => $invoiceMeta['currency'] ?? 'INR',
                ];

                $shipment = $this->shipmentRepository->createShipment($shipmentPayload);

                // B. Insert Boxes
                $this->shipmentRepository->createBoxes($shipment->id, $boxes);

                // C. Insert Invoice & Invoice Line Items
                $this->shipmentRepository->createInvoice($shipment->id, $invoiceMeta, $invoiceItems);

                // Return fully loaded shipment
                return $this->shipmentRepository->findById($shipment->id);
            });
        } catch (\Throwable $e) {
            // Clean up any uploaded files if transaction or processing fails
            foreach ($uploadedPaths as $path) {
                if ($path && Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }
            throw $e;
        }
    }

    public function getShipmentById(int $id): ?ShipmentDetail
    {
        return $this->shipmentRepository->findById($id);
    }

    public function getShipmentByAwb(string $awb): ?ShipmentDetail
    {
        return $this->shipmentRepository->findByShipmentNo($awb);
    }

    public function getAllShipments(int $perPage = 15, int $page = 1, array $filters = [])
    {
        return $this->shipmentRepository->getAll($perPage, $page, $filters);
    }

    public function getDashboardStats(array $filters = []): array
    {
        return $this->shipmentRepository->getDashboardStats($filters);
    }

    public function deleteShipment(int $id): bool
    {
        return $this->shipmentRepository->delete($id);
    }

    /**
     * Generate unique AWB number e.g. EXP261108A1B2
     */
    protected function generateUniqueShipmentNo(): string
    {
        do {
            $candidate = 'EXP' . date('ymd') . strtoupper(Str::random(4));
        } while (ShipmentDetail::where('shipment_no', $candidate)->exists());

        return $candidate;
    }
}

