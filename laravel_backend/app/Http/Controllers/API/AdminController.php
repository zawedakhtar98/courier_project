<?php

namespace App\Http\Controllers\API;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\CountryResource;
use App\Http\Resources\ServicePartnerResource;
use App\Http\Resources\ZoneMasterResource;
use App\Models\Country;
use App\Models\ServicePartnerZoneRate;
use App\Models\ZoneCountryMapping;
use App\Models\ZoneMaster;
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

    //Country Master Logic
    public function addCountries(Request $request)
    {
        $request->validate(
            [
                'name' => 'required|string|max:255|unique:country,name',
                'code' => 'required|string|max:10|unique:country,short_name',
            ],
            [
                'name.required' => "Please enter country name",
                'name.string' => 'Country must contains string only.',
                'name.max' => 'Name should be less than 300 character',
                'name.unique' => 'Country name already exists.',
                'code.required' => "Please enter country code",
                'code.string' => 'Country code must contains string only.',
                'code.max' => 'Country code should be less than 10 character',
                'code.unique' => 'Country code already exists.',
            ]
        );

        $data = [
            'name' => $request->name,
            'short_name' => $request->code,
            'status' => 'active'
        ];
        $country_data = Country::create($data);
        return ApiResponse::success(CountryResource::make($country_data), $request->name . " created successfully!");
    }

    public function updateCountry(Request $request, int $id)
    {
        $request->validate(
            [
                'name' => 'required|string|max:255|unique:country_master,name',
                'code' => 'required|string|max:10|unique:country_master,code',
            ],
            [
                'name.required' => "Please enter country name",
                'name.string' => 'Country must contains string only.',
                'name.max' => 'Name should be less than 300 character',
                'name.unique' => 'Country name already exists.',
                'code.required' => "Please enter country code",
                'code.string' => 'Country code must contains string only.',
                'code.max' => 'Country code should be less than 10 character',
                'code.unique' => 'Country code already exists.',
            ]
        );

        $data = [
            'name' => $request->name,
            'short_name' => $request->code
        ];
        $country_data = Country::where('id', $id)->update($data);
        return ApiResponse::success([], $request->name . "Updated successfully!");
    }

    public function getCountryList()
    {
        $country_data = Country::all();
        return ApiResponse::success(CountryResource::collection($country_data), "Countries fetched successfully!");
    }

    public function deleteCountry(Request $request)
    {
        Country::where('id', $request->id)->delete();
        return ApiResponse::success([], "Country deleted successfully!");
    }

    public function updateCountryStatus(Request $request)
    {
        $request->validate(
            [
                'status' => 'required|string|in:active,inactive',
            ],
            [
                'status.required' => "Please enter status",
                'status.string' => 'Status must contains string only.',
                'status.in' => 'Status must be active or inactive',
            ]
        );
        $data = [
            'status' => $request->status
        ];
        $country_data = Country::where('id', $request->id)->update($data);
        return ApiResponse::success([], $request->name . " updated successfully!");
    }

    // Zone Master Functionality
    public function addZone(Request $request)
    {
        $request->validate(
            [
                'name' => 'required'
            ],
            [
                'name.required' => 'Enter Zone name'
            ]
        );

        $zone = ZoneMaster::create([
            'zone_name' => $request->name,
            'status' => 'active'
        ]);

        return ApiResponse::success(ZoneMasterResource::make($zone), "Zone created successfully!");
    }

    public function updateZone(Request $request, $id)
    {
        $request->validate(
            [
                'name' => 'required'
            ],
            [
                'name.required' => 'Enter Zone name'
            ]
        );

        $zone = ZoneMaster::where('id', $id)->update([
            'zone_name' => $request->name
        ]);
        return ApiResponse::success(ZoneMasterResource::make($zone), "Zone updated successfully!");
    }

    public function getZoneList()
    {
        $zone = ZoneMaster::with('mapCountries')->get();
        return ApiResponse::success(ZoneMasterResource::collection($zone), "Zone list fetched successfully!");
    }

    public function deleteZone(Request $request)
    {
        ZoneMaster::where('id', $request->id)->delete();
        return ApiResponse::success([], "Zone deleted successfully!");
    }

    public function addZoneCountries(Request $request)
    {
        $request->validate(
            [
                'zone_id' => 'required',
                'country_id' => 'required',
            ],
            [
                'zone_id.required' => 'Enter Zone ID',
                'country_id.required' => 'Enter Country ID',
            ]
        );

        $zone = ZoneMaster::where('id', $request->zone_id)->first();
        if (!$zone) {
            return ApiResponse::error([], "Zone not found");
        }

        $country = Country::where('id', $request->country_id)->first();
        if (!$country) {
            return ApiResponse::error([], "Country not found");
        }

        $zoneCountryMapping = ZoneCountryMapping::where('zone_id', $request->zone_id)->where('country_id', $request->country_id)->first();
        if ($zoneCountryMapping) {
            return ApiResponse::error([], "Zone Country Mapping already exists");
        }

        $zoneCountryMapping = ZoneCountryMapping::create([
            'zone_id' => $request->zone_id,
            'country_id' => $request->country_id,
        ]);

        return ApiResponse::success([], "Zone Country Mapped successfully!");
    }

    public function saveServicePartnerRates(Request $request)
    {
        $request->validate([
            'service_partner_id' => 'required|integer',
            'rates' => 'required|array',
        ]);

        $partnerId = $request->service_partner_id;

        // Delete existing rates for this partner to replace them with the new bulk upload
        ServicePartnerZoneRate::where('service_partner_id', $partnerId)->delete();

        $ratesToInsert = [];
        foreach ($request->rates as $rateData) {
            $ratesToInsert[] = [
                'service_partner_id' => $partnerId,
                'zone_id' => $rateData['zone_id'],
                'package_type' => $rateData['package_type'],
                'weight_from' => $rateData['weight_from'],
                'weight_to' => $rateData['weight_to'],
                'rate' => $rateData['rate'],
                'rate_type' => $rateData['rate_type'],
                'currency' => $rateData['currency'] ?? 'INR',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        ServicePartnerZoneRate::insert($ratesToInsert);

        return ApiResponse::success([], "Rates saved successfully!");
    }

    public function getServicePartnerRates($servicePartnerId)
    {
        $rates = ServicePartnerZoneRate::where('service_partner_id', $servicePartnerId)->get();
        return ApiResponse::success($rates, "Rates fetched successfully!");
    }

    public function saveShipment(Request $request)
    {
        $validated = $request->validate([
            'awb_number' => 'required|string',
            'service_partner_id' => 'nullable|integer',
            'user_id' => 'nullable|integer',
            'pickup_date' => 'nullable|date',

            // Sender Details
            'sender_name' => 'nullable|string',
            'sender_company_name' => 'nullable|string',
            'sender_contact_person_name' => 'nullable|string',
            'sender_address_line_1' => 'nullable|string',
            'sender_address_line_2' => 'nullable|string',
            'sender_address_line_3' => 'nullable|string',
            'sender_city' => 'nullable|string',
            'sender_state' => 'nullable|string',
            'sender_pincode' => 'nullable|string',
            'sender_type' => 'nullable|in:individual,business',
            'sender_kyc_type' => 'nullable|in:aadhaar,passport,gstin',
            'sender_kyc_number' => 'nullable|string',
            'sender_telephone' => 'nullable|string',
            'sender_email' => 'nullable|email',

            // Receiver Details
            'receiver_name' => 'nullable|string',
            'receiver_company_name' => 'nullable|string',
            'receiver_contact_person_name' => 'nullable|string',
            'receiver_address_line_1' => 'nullable|string',
            'receiver_address_line_2' => 'nullable|string',
            'receiver_address_line_3' => 'nullable|string',
            'receiver_city' => 'nullable|string',
            'receiver_state' => 'nullable|string',
            'receiver_pincode' => 'nullable|string',
            'receiver_type' => 'nullable|in:individual,business',
            'receiver_vat_tax_id' => 'nullable|string',
            'receiver_telephone' => 'nullable|string',
            'receiver_email' => 'nullable|email',
            'receiver_country_id' => 'nullable|integer',

            // Metadata
            'payment_status' => 'nullable|in:paid,pending,cancel',
            'status' => 'nullable|in:draft,pending,cancel,intransit,delivered',
            'created_by' => 'nullable|in:employee,admin,customer',
            'goods_type' => 'nullable|string',
            'actual_weight' => 'nullable|numeric',
            'chargeable_weight' => 'nullable|numeric',
            'shipment_total_cost' => 'nullable|numeric',
        ]);

        try {
            // Adjust the Model name according to your actual setup
            $shipment = \App\Models\ShipmentDetail::create($validated);

            return response()->json([
                'status' => true,
                'message' => 'Shipment stored successfully',
                'data' => $shipment
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error storing shipment: ' . $e->getMessage()
            ], 500);
        }
    }
}
