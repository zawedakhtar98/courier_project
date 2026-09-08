<?php

namespace App\Http\Controllers\API;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\ServicePartnerResource;
use App\Services\ServicePartnerServices;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    public function __construct(protected ServicePartnerServices $servicePartnerServices)
    {
        $this->servicePartnerServices = $servicePartnerServices;
    }

    public function addNewServicePartner(Request $request)
    {
        $request->validate(
            [
                'name' => 'required|string|max:255|unique:servicepartner,name',
                'service_code' => 'required|string|max:50|unique:servicepartner,service_code',
            ],
            [
                'name.required' => "Please enter service partner name",
                'name.string' => 'Service partner must contains string only.',
                'name.max' => 'Name should be less than 300 character',
                'name.unique' => 'Service partner name already exists.',
                'service_code.required' => 'Please enter service code',
                'service_code.unique' => 'Service code already exists.',
            ]
        );

        $data = [
            'name' => $request->name,
            'service_code' => $request->service_code,
            'status' => 'active'
        ];
        $partner_data = Collection::make($this->servicePartnerServices->createServicePartner($data));
        return ApiResponse::success($partner_data, $request->name . " created successfully!");
    }

    public function updateServicePartner(Request $request, $id)
    {
        $request->validate(
            [
                'name' => 'required|string|max:255|unique:servicepartner,name,' . $id,
                'service_code' => 'string|max:50|unique:servicepartner,service_code,' . $id,
                'status' => 'string|in:active,inactive',
            ],
            [
                'name.required' => "Please enter service partner name",
                'name.string' => 'Service partner must contains string only.',
                'name.max' => 'Name should be less than 300 character',
                'name.unique' => 'Service partner name already exists.',
                'service_code.unique' => 'Service code already exists.',
                'status.in' => 'Status must be active or inactive',
            ]
        );

        $data = [
            'name' => $request->name,
            'service_code' => $request->service_code,
            'status' => $request->status
        ];
        $partner_data = Collection::make($this->servicePartnerServices->updateServicePartner($data, $id));
        return ApiResponse::success($partner_data, $request->name . " updated successfully!");
    }

    public function deleteServicePartner(Request $request)
    {
        $partner_data = Collection::make($this->servicePartnerServices->deleteServicePartner($request->id));
        return ApiResponse::success($partner_data, $request->name . " deleted successfully!");
    }

    //pass input request param from the postman
    //http://127.0.0.1:8000/api/admin/service-partner/getAll-partners?perPage=30&page=2
    public function getAllServicePartners(Request $request)
    {
        $perPage = $request->perPage ?? 20;
        $partner_data = ServicePartnerResource::collection($this->servicePartnerServices->getAllServicePartner($perPage));
        return ApiResponse::success($partner_data, "Service partners fetched successfully!");
    }
}
