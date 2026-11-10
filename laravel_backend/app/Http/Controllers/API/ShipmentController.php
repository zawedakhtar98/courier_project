<?php

namespace App\Http\Controllers\API;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Services\ShipmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ShipmentController extends Controller
{
    public function __construct(protected ShipmentService $shipmentService) {}

    /**
     * Book a new shipment at final step ("Book Now")
     */
    public function bookShipment(Request $request)
    {
        $request->validate([
            // Sender validation
            'sender_name'            => 'required|string|max:255',
            'sender_company_name'    => 'required|string|max:255',
            'sender_address_line_1'  => 'required|string|max:255',
            'sender_mobile'          => 'required|string|max:25',
            'sender_email'           => 'required|email|max:255',
            // 'sender_city'            => 'required|string|max:100',
            // 'sender_state'           => 'required|string|max:100',
            // 'sender_pincode'         => 'required|string|max:20',
            'sender_kyc_type'        => 'required|string|max:50',
            'sender_kyc_number'      => 'required|string|max:100',
            'sender_kyc_front'       => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'sender_kyc_back'        => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',

            // Consignee validation
            'consignee_name'           => 'required|string|max:255',
            'consignee_company_name'   => 'required|string|max:255',
            'consignee_address_line_1' => 'required|string|max:255',
            'consignee_mobile_no'      => 'required|string|max:25',
            'consignee_email'          => 'required|email|max:255',
            'destination'              => 'required',
            'service_partner_id'       => 'required',
            'package_type'             => 'required|string|max:50',

            // Box and Invoice items
            'boxes'                    => 'required',
            'invoice_meta'             => 'required',
            'invoice_items'            => 'required',
        ]);

        try {
            $user = $request->user();
            Log::info('shipment function');
            Log::info($user);
            $shipment = $this->shipmentService->bookShipment(
                $request->all(),
                $request->allFiles(),
                $user ? $user->id : null
            );

            return ApiResponse::success($shipment, 'Shipment booked successfully!', 201);
        } catch (\Throwable $e) {
            Log::error('Shipment Booking Exception: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return ApiResponse::error($e->getMessage(), 422);
        }
    }

    /**
     * Get shipment details by ID
     */
    public function getShipmentById($id)
    {
        try {
            $shipment = $this->shipmentService->getShipmentById((int)$id);
            if (!$shipment) {
                return ApiResponse::error('Shipment not found', 404);
            }
            return ApiResponse::success($shipment, 'Shipment fetched successfully');
        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    /**
     * Get shipment details by AWB number
     */
    public function getShipmentByAwb($awb)
    {
        try {
            $shipment = $this->shipmentService->getShipmentByAwb($awb);
            if (!$shipment) {
                return ApiResponse::error('Shipment not found with AWB ' . $awb, 404);
            }
            return ApiResponse::success($shipment, 'Shipment fetched successfully');
        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    /**
     * Get paginated shipment list with optional filters and search
     */
    public function getShipmentList(Request $request)
    {
        try {
            $perPage = (int)$request->get('per_page', 15);
            $page = (int)$request->get('page', 1);
            $filters = [
                'search'  => $request->get('search', ''),
                'service' => $request->get('service', ''),
                'status'  => $request->get('status', ''),
            ];
            $list = $this->shipmentService->getAllShipments($perPage, $page, $filters);
            return ApiResponse::success($list, 'Shipments retrieved successfully');
        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    /**
     * Get aggregated shipment statistics for dashboard KPI cards and overview
     */
    public function getDashboardStats(Request $request)
    {
        try {
            $filters = [
                'timeframe' => $request->get('timeframe', 'all'),
                'from_date' => $request->get('from_date'),
                'to_date'   => $request->get('to_date'),
            ];
            $stats = $this->shipmentService->getDashboardStats($filters);
            return ApiResponse::success($stats, 'Dashboard statistics retrieved successfully');
        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    /**
     * Delete shipment by ID
     */
    public function deleteShipment($id)
    {
        try {
            $deleted = $this->shipmentService->deleteShipment((int)$id);
            if (!$deleted) {
                return ApiResponse::error('Shipment not found or could not be deleted', 404);
            }
            return ApiResponse::success(null, 'Shipment deleted successfully');
        } catch (\Throwable $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }
}
