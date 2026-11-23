<?php

namespace App\Http\Controllers\API;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\CountryResource;
use App\Http\Resources\ServicePartnerResource;
use App\Http\Resources\ServicePartnerZoneWiserateResource;
use App\Http\Resources\ShipmentAdditionalChargesResource;
use App\Http\Resources\ZoneMasterResource;
use App\Models\Country;
use App\Models\ServicePartner;
use App\Models\ServicePartnerZoneRate;
use App\Models\ShipmentAdditionalCharges;
use App\Models\ShipmentChargesMaster;
use App\Models\ZoneCountryMapping;
use App\Models\ZoneMaster;
use App\Services\CountryService;
use App\Services\ServicePartnerServices;
use App\Services\ServicePartnerZoneRateService;
use App\Services\ShipmentAdditionalChargeService;
use App\Services\ShipmentChargesMasterService;
use App\Services\ZoneMasterService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    public function __construct(
        protected ServicePartnerServices $servicePartnerServices,
        protected ZoneMasterService $zoneMasterService,
        protected CountryService $countryService,
        protected ServicePartnerZoneRateService $servicePartnerZoneRateService,
        protected ShipmentAdditionalChargeService $shipmentAdditionalChargeService,
        protected ShipmentChargesMasterService $shipmentChargesMasterService,
    ) {}

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
        $country_data = $this->countryService->create($data);
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
        $this->countryService->update($data, $id);
        return ApiResponse::success([], $request->name . "Updated successfully!");
    }

    public function getCountryList()
    {
        $country_data = Cache::remember('country_list', now()->addDays(10), function () {
            return $this->countryService->findAll();
        });
        return ApiResponse::success(CountryResource::collection($country_data), "Countries fetched successfully!");
    }

    public function deleteCountry(Request $request)
    {
        $country_data = $this->countryService->delete($request->id);
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
        $this->countryService->update($data, $request->id);
        return ApiResponse::success([], " updated successfully!");
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

        $zone = $this->zoneMasterService->create([
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
        $zone = $this->zoneMasterService->update(['zone_name' => $request->name], $id);
        return ApiResponse::success(ZoneMasterResource::make($zone), "Zone updated successfully!");
    }

    public function getZoneList()
    {
        $zone = $this->zoneMasterService->getAllZone();
        return ApiResponse::success(ZoneMasterResource::collection($zone), "Zone list fetched successfully!");
    }

    public function deleteZone($id)
    {
        $this->zoneMasterService->deleteZone($id);
        return ApiResponse::success([], "Zone deleted successfully!");
    }

    public function MapZoneWithCountry(Request $request)
    {
        $request->validate(
            [
                'zone_id' => 'required',
                'country_id' => 'required',
            ],
            [
                'zone_id.required' => 'Please select a Zone',
                'country_id.required' => 'Please select at least one Country',
            ]
        );

        $zone = $this->zoneMasterService->findById($request->zone_id);
        if (!$zone) {
            return ApiResponse::error([], "Zone not found");
        }

        $countryIds = is_array($request->country_id) ? $request->country_id : [$request->country_id];
        $mappedCount = 0;
        $skippedCount = 0;

        foreach ($countryIds as $cId) {
            if (empty($cId)) continue;
            $country = $this->countryService->findById((int)$cId);
            if (!$country) continue;

            $exists = ZoneCountryMapping::where('zone_id', $zone->id)
                ->where('country_id', $country->id)
                ->exists();

            if (!$exists) {
                ZoneCountryMapping::create([
                    'zone_id' => $zone->id,
                    'country_id' => $country->id,
                ]);
                $mappedCount++;
            } else {
                $skippedCount++;
            }
        }

        return ApiResponse::success([
            'mapped_count' => $mappedCount,
            'skipped_count' => $skippedCount,
        ], "Zone Country Mapped successfully! ({$mappedCount} added, {$skippedCount} already mapped)");
    }

    public function unmapZoneCountry(Request $request)
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

        ZoneCountryMapping::where('zone_id', $request->zone_id)
            ->where('country_id', $request->country_id)
            ->delete();

        return ApiResponse::success([], "Country removed from zone successfully!");
    }

    public function bulkMapZoneWithCountry(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240',
            'mode' => 'nullable|in:append,overwrite',
        ], [
            'file.required' => 'Please select a CSV file to upload.',
            'file.max' => 'The file size must not exceed 10MB.',
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, ['csv', 'txt'])) {
            return ApiResponse::error([], 'Invalid file format. Please upload a .csv file.');
        }

        $mode = $request->input('mode', 'append');

        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            return ApiResponse::error([], 'Failed to open the uploaded file.');
        }

        // Read header row
        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            return ApiResponse::error([], 'The uploaded CSV file is empty.');
        }

        // Strip UTF-8 BOM if present
        $bom = pack('H*', 'EFBBBF');
        $header[0] = preg_replace("/^$bom/", '', $header[0]);

        // Identify column indexes
        $zoneColIndex = -1;
        $countryCodeIndex = -1;
        $countryNameIndex = -1;

        foreach ($header as $idx => $colName) {
            $cleanCol = strtolower(trim($colName));
            if ($zoneColIndex === -1 && str_contains($cleanCol, 'zone')) {
                $zoneColIndex = $idx;
            } elseif ($countryCodeIndex === -1 && (str_contains($cleanCol, 'code') || str_contains($cleanCol, 'iso') || str_contains($cleanCol, 'short'))) {
                $countryCodeIndex = $idx;
            } elseif ($countryNameIndex === -1 && str_contains($cleanCol, 'country')) {
                $countryNameIndex = $idx;
            }
        }

        // Fallbacks if header names differ
        if ($zoneColIndex === -1) $zoneColIndex = 0;
        if ($countryCodeIndex === -1 && $countryNameIndex === -1) {
            $countryCodeIndex = 1;
            if (isset($header[2])) $countryNameIndex = 2;
        } elseif ($countryCodeIndex === -1 && $countryNameIndex !== -1) {
            $countryCodeIndex = $countryNameIndex;
        }

        // Preload all countries into memory for O(1) lookup
        $allCountries = Country::all();
        $countriesByCode = [];
        $countriesByName = [];
        foreach ($allCountries as $c) {
            if (!empty($c->short_name)) {
                $countriesByCode[strtoupper(trim($c->short_name))] = $c;
            }
            if (!empty($c->name)) {
                $countriesByName[strtolower(trim($c->name))] = $c;
            }
        }

        // Preload all zones
        $allZones = ZoneMaster::all();
        $zonesByName = [];
        foreach ($allZones as $z) {
            $zonesByName[strtolower(trim($z->zone_name))] = $z;
        }

        $clearedZones = [];
        $totalRows = 0;
        $mappedCount = 0;
        $skippedCount = 0;
        $unmatchedRows = [];

        DB::beginTransaction();
        try {
            $rowNum = 1; // header was row 1
            while (($row = fgetcsv($handle)) !== false) {
                $rowNum++;

                // Skip blank lines
                if (empty($row) || (count($row) === 1 && trim($row[0]) === '')) {
                    continue;
                }

                $totalRows++;

                $rawZone = isset($row[$zoneColIndex]) ? trim($row[$zoneColIndex]) : '';
                $rawCode = isset($row[$countryCodeIndex]) ? trim($row[$countryCodeIndex]) : '';
                $rawName = ($countryNameIndex !== -1 && isset($row[$countryNameIndex])) ? trim($row[$countryNameIndex]) : '';

                if ($rawZone === '' || ($rawCode === '' && $rawName === '')) {
                    $unmatchedRows[] = "Row {$rowNum}: Zone name or country identifier is missing.";
                    continue;
                }

                // Match or auto-create Zone
                $zoneKey = strtolower($rawZone);
                if (!isset($zonesByName[$zoneKey])) {
                    $newZone = ZoneMaster::create([
                        'zone_name' => $rawZone,
                        'status' => 'active',
                    ]);
                    $zonesByName[$zoneKey] = $newZone;
                }
                $zone = $zonesByName[$zoneKey];

                // If overwrite mode and this zone hasn't been cleared yet, clear its previous mappings
                if ($mode === 'overwrite' && !in_array($zone->id, $clearedZones, true)) {
                    ZoneCountryMapping::where('zone_id', $zone->id)->delete();
                    $clearedZones[] = $zone->id;
                }

                // Match country:
                // 1) By code (ISO 2-letter, e.g. US, IN)
                // 2) By country name (e.g. United States)
                $country = null;
                if ($rawCode !== '' && isset($countriesByCode[strtoupper($rawCode)])) {
                    $country = $countriesByCode[strtoupper($rawCode)];
                } elseif ($rawName !== '' && isset($countriesByName[strtolower($rawName)])) {
                    $country = $countriesByName[strtolower($rawName)];
                } elseif ($rawCode !== '' && isset($countriesByName[strtolower($rawCode)])) {
                    $country = $countriesByName[strtolower($rawCode)];
                }

                if (!$country) {
                    $countryLabel = $rawCode ?: $rawName;
                    $unmatchedRows[] = "Row {$rowNum}: Country '{$countryLabel}' not found in database.";
                    continue;
                }

                // Check if mapping already exists
                $exists = ZoneCountryMapping::where('zone_id', $zone->id)
                    ->where('country_id', $country->id)
                    ->exists();

                if ($exists) {
                    $skippedCount++;
                } else {
                    ZoneCountryMapping::create([
                        'zone_id' => $zone->id,
                        'country_id' => $country->id,
                    ]);
                    $mappedCount++;
                }
            }

            fclose($handle);
            DB::commit();

            return ApiResponse::success([
                'total_rows' => $totalRows,
                'mapped_count' => $mappedCount,
                'skipped_count' => $skippedCount,
                'unmatched_count' => count($unmatchedRows),
                'unmatched_rows' => array_slice($unmatchedRows, 0, 50),
            ], "Bulk mapping completed! {$mappedCount} mapped, {$skippedCount} skipped, " . count($unmatchedRows) . " unmatched.");
        } catch (\Exception $e) {
            DB::rollBack();
            if (is_resource($handle)) {
                fclose($handle);
            }
            Log::error("Bulk zone mapping error: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return ApiResponse::error([], 'Failed to process bulk mapping: ' . $e->getMessage());
        }
    }

    // Service Partner Zone Rate Functionality
    public function addServicePartnerZoneRate(Request $request)
    {
        $validated = $request->validate(
            [
                'servicePartner'   => 'required|integer',
                'packageType'      => 'required|string',
                'rateType'         => 'required|string',
                'weightFrom'       => 'required|numeric',
                'weightTo'         => 'required|numeric',
                'currency'         => 'required|string',
                'status'           => 'required|string',
                'selectedZones'    => 'required|array',
                'zoneRates'        => 'required|array',
            ],
            [
                'servicePartner.required' => 'Select Service Partner',
                'packageType.required' => 'Select Package Type',
                'rateType.required' => 'Select Rate Type',
                'weightFrom.required' => 'Enter Weight From',
                'weightTo.required' => 'Enter Weight To',
                'currency.required' => 'Select Currency',
                'selectedZones.required' => 'Select Zones',
                'zoneRates.required' => 'Enter Zone Rates',
            ]
        );
        try {
            DB::beginTransaction();
            $rows = [];
            $now = now();

            foreach ($validated['selectedZones'] as $zoneId) {
                // zoneRates keys come in as strings (json object keys)
                $rate = $validated['zoneRates'][$zoneId] ?? $validated['zoneRates'][(string) $zoneId] ?? null;

                if ($rate === null) {
                    continue; // skip zones with no rate provided
                }

                $rows[] = [
                    'service_partner_id' => $validated['servicePartner'],
                    'zone_id'            => $zoneId,
                    'package_type'       => $validated['packageType'],
                    'weight_from'        => $validated['weightFrom'],
                    'weight_to'          => $validated['weightTo'],
                    'rate'               => $rate,
                    'rate_type'          => $validated['rateType'],
                    'currency'           => ($validated['currency'] === '₹') ? 'INR' : 'USD',
                    'created_at'         => $now,
                    'updated_at'         => $now,
                ];
            }
            $this->servicePartnerZoneRateService->create($rows);
            DB::commit();
            return ApiResponse::success([], "Service Partner Zone Rate created successfully!");
        } catch (\Throwable $e) {
            DB::rollBack();
            if ($e instanceof \Illuminate\Database\QueryException && $e->errorInfo[1] == 1062) {
                return ApiResponse::error('A rate configuration for this package type and weight range already exists.');
            }
            return ApiResponse::error($e->getMessage());
        }
    }

    public function bulkUploadServicePartnerZoneRates(Request $request)
    {
        $request->validate([
            'file'               => 'required|file|max:10240',
            'service_partner_id' => 'required|integer|exists:servicepartner,id',
            'package_type'       => 'required|string',
            'currency'           => 'nullable|string',
            'rate_type_mode'     => 'nullable|string|in:threshold,all_flat,all_per_kg',
            'per_kg_threshold'   => 'nullable|numeric|min:0',
            'mode'               => 'nullable|string|in:overwrite,upsert',
        ], [
            'file.required'               => 'Please select a CSV file to upload.',
            'service_partner_id.required' => 'Please select a service partner.',
            'service_partner_id.exists'   => 'The selected service partner does not exist.',
            'package_type.required'       => 'Please select a package type (doc / nondoc).',
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, ['csv', 'txt'])) {
            return ApiResponse::error('Invalid file format. Please upload a .csv file.');
        }

        $servicePartnerId = (int)$request->input('service_partner_id');
        $servicePartner = ServicePartner::find($servicePartnerId);
        $partnerName = $servicePartner ? trim($servicePartner->name) : '';
        $partnerCode = $servicePartner ? trim($servicePartner->service_code ?? '') : '';
        $packageType = strtolower(trim($request->input('package_type')));
        $currency = $request->input('currency', 'INR');

        $rateTypeMode = $request->input('rate_type_mode', 'threshold');
        $perKgThreshold = (float)$request->input('per_kg_threshold', 30.00);
        $mode = $request->input('mode', 'overwrite');

        // Optional: filter by specific zone IDs selected in UI
        $selectedZoneIds = [];
        if ($request->has('selected_zone_ids')) {
            $rawSelected = $request->input('selected_zone_ids');
            if (is_string($rawSelected)) {
                $decoded = json_decode($rawSelected, true);
                if (is_array($decoded)) {
                    $selectedZoneIds = array_map('intval', $decoded);
                } elseif (str_contains($rawSelected, ',')) {
                    $selectedZoneIds = array_map('intval', explode(',', $rawSelected));
                } elseif (is_numeric($rawSelected)) {
                    $selectedZoneIds = [(int)$rawSelected];
                }
            } elseif (is_array($rawSelected)) {
                $selectedZoneIds = array_map('intval', $rawSelected);
            }
        }

        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            return ApiResponse::error('Failed to open the uploaded file.');
        }

        // Read header row
        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            return ApiResponse::error('The uploaded CSV file is empty.');
        }

        // Strip UTF-8 BOM if present
        $bom = pack('H*', 'EFBBBF');
        $header[0] = preg_replace("/^$bom/", '', $header[0]);

        // Detect weight columns
        // Standard formats:
        // Format A: "ToWeight, 1, 2, 3..." (Single weight column)
        // Format B: "FromWeight, ToWeight, 1, 2, 3..." (Two weight columns)
        $firstCol = strtolower(trim($header[0]));
        $secondCol = isset($header[1]) ? strtolower(trim($header[1])) : '';

        $hasFromWeight = false;
        $zoneStartIndex = 1;

        if (str_contains($firstCol, 'from') && str_contains($secondCol, 'to')) {
            $hasFromWeight = true;
            $zoneStartIndex = 2;
        } elseif (str_contains($firstCol, 'weight') || str_contains($firstCol, 'toweight') || str_contains($firstCol, 'to_weight')) {
            $hasFromWeight = false;
            $zoneStartIndex = 1;
        }

        // Preload zones into memory with multiple indexing strategies
        $allZones = ZoneMaster::all();
        $zonesByName = [];
        $zonesByNormalized = [];
        $zonesById = [];

        foreach ($allZones as $z) {
            $rawName = trim($z->zone_name);
            $lowerName = strtolower($rawName);
            $normName = preg_replace('/[^a-z0-9]/', '', $lowerName);

            $zonesByName[$lowerName] = $z;
            if ($normName !== '' && !isset($zonesByNormalized[$normName])) {
                $zonesByNormalized[$normName] = $z;
            }
            $zonesById[$z->id] = $z;
        }

        // Service partner identifiers for prefix resolution
        $spName = strtolower(trim($partnerName));
        $spNameNorm = preg_replace('/[^a-z0-9]/', '', $spName);
        $spCode = strtolower(trim($partnerCode));
        $spCodeNorm = preg_replace('/[^a-z0-9]/', '', $spCode);

        // Map column indices to zone IDs
        $zoneColumnMap = [];
        $unmatchedHeaders = [];
        $skippedUnselectedHeaders = [];

        for ($i = $zoneStartIndex; $i < count($header); $i++) {
            $rawHeader = trim($header[$i]);
            if ($rawHeader === '') continue;

            $headerClean = strtolower($rawHeader);
            $headerNorm = preg_replace('/[^a-z0-9]/', '', $headerClean);
            $matchedZone = null;

            // Generate search candidates in strict order of specificity:
            $candidates = [];

            // 1. Direct match (e.g. "Zone_13", "Zone_A", "A", "DHL ZONE")
            $candidates[] = $headerClean;

            // 2. Service Partner-specific variations (e.g. "DHL ZONE", "DHL 1", "DHL_1", "DHL Zone 1", "DHL A", "DHL_Zone_A")
            if ($spName !== '') {
                $candidates[] = "{$spName} {$headerClean}";
                $candidates[] = "{$spName}_{$headerClean}";
                $candidates[] = "{$spName}-{$headerClean}";
                $candidates[] = "{$spName} zone {$headerClean}";
                $candidates[] = "{$spName}_zone_{$headerClean}";
                $candidates[] = "{$spName}-zone-{$headerClean}";
                $candidates[] = "{$spName}zone{$headerClean}";
                if (str_contains($headerClean, 'zone')) {
                    $candidates[] = "{$spName} {$headerClean}";
                    $candidates[] = "{$spName}_{$headerClean}";
                }
            }
            if ($spCode !== '' && $spCode !== $spName) {
                $candidates[] = "{$spCode} {$headerClean}";
                $candidates[] = "{$spCode}_{$headerClean}";
                $candidates[] = "{$spCode}_zone_{$headerClean}";
            }

            // 3. Standard "Zone_" / "Zone " / "Zone-" / "ZN-" prefix variations
            $candidates[] = "zone_{$headerClean}";
            $candidates[] = "zone {$headerClean}";
            $candidates[] = "zone-{$headerClean}";
            $candidates[] = "zone{$headerClean}";
            $candidates[] = "zn-{$headerClean}";
            $candidates[] = "zn_{$headerClean}";

            // 4. Stripping "Zone_" / "Zone " prefix if present in the header
            // e.g. CSV header is "Zone_A" or "Zone_14", but DB has just "A" or "14"
            if (preg_match('/^(?:zone|zn)[\s_\-:]*(.+)$/i', $rawHeader, $matches)) {
                $strippedZone = strtolower(trim($matches[1]));
                if ($strippedZone !== '') {
                    $candidates[] = $strippedZone;
                    if ($spName !== '') {
                        $candidates[] = "{$spName} {$strippedZone}";
                        $candidates[] = "{$spName}_{$strippedZone}";
                    }
                }
            }

            // 5. Stripping partner prefix if CSV header already contains partner name/code
            // e.g. CSV header is "DHL Zone 1" or "DHL_A", but DB has "Zone_1" or "A"
            if ($spName !== '' && str_starts_with($headerClean, $spName)) {
                $strippedPartner = trim(substr($headerClean, strlen($spName)), " _-:");
                if ($strippedPartner !== '') {
                    $candidates[] = $strippedPartner;
                    $candidates[] = "zone_{$strippedPartner}";
                    $candidates[] = "zone {$strippedPartner}";
                    if (preg_match('/^(?:zone)[\s_\-:]*(.+)$/i', $strippedPartner, $m2)) {
                        $candidates[] = trim($m2[1]);
                    }
                }
            }

            // Phase A: Check exact candidate strings in $zonesByName
            foreach ($candidates as $candidate) {
                if ($candidate !== '' && isset($zonesByName[$candidate])) {
                    $matchedZone = $zonesByName[$candidate];
                    break;
                }
            }

            // Phase B: Check normalized alphanumeric candidates in $zonesByNormalized (ignoring spaces, underscores, hyphens)
            if (!$matchedZone) {
                $normCandidates = [];
                $normCandidates[] = $headerNorm;
                if ($spNameNorm !== '') {
                    $normCandidates[] = $spNameNorm . $headerNorm;
                    $normCandidates[] = $spNameNorm . 'zone' . $headerNorm;
                }
                $normCandidates[] = 'zone' . $headerNorm;
                $normCandidates[] = 'zn' . $headerNorm;

                if (preg_match('/^(?:zone|zn)(.+)$/i', $headerNorm, $mNorm)) {
                    $normCandidates[] = $mNorm[1];
                    if ($spNameNorm !== '') {
                        $normCandidates[] = $spNameNorm . $mNorm[1];
                    }
                }

                foreach ($normCandidates as $nc) {
                    if ($nc !== '' && isset($zonesByNormalized[$nc])) {
                        $matchedZone = $zonesByNormalized[$nc];
                        break;
                    }
                }
            }

            // Phase C: Fallback by integer ID if numeric
            if (!$matchedZone && is_numeric($rawHeader) && isset($zonesById[(int)$rawHeader])) {
                $matchedZone = $zonesById[(int)$rawHeader];
            }

            // Phase D: If not found in Zone Master, skip this column and track as unmatched
            if (!$matchedZone) {
                $unmatchedHeaders[] = $rawHeader;
                continue;
            }

            // Phase E: If user filtered by specific zones in UI, skip zones that were not selected
            if (!empty($selectedZoneIds) && !in_array($matchedZone->id, $selectedZoneIds, true)) {
                $skippedUnselectedHeaders[] = $matchedZone->zone_name;
                continue;
            }

            $zoneColumnMap[$i] = $matchedZone->id;
        }

        if (empty($zoneColumnMap)) {
            fclose($handle);
            $msg = 'No valid zone columns found in the header row that match your criteria.';
            if (!empty($selectedZoneIds) && !empty($skippedUnselectedHeaders)) {
                $msg .= ' The zones in the CSV were found in Zone Master, but none matched your selected zones (' . implode(', ', $skippedUnselectedHeaders) . ').';
            } elseif (!empty($unmatchedHeaders)) {
                $msg .= ' The following column(s) were not recognized in Zone Master: ' . implode(', ', $unmatchedHeaders) . '.';
            }
            return ApiResponse::error($msg);
        }

        $recordsToInsert = [];
        $previousWeight = 0.00;
        $totalSlabs = 0;
        $minWeight = null;
        $maxWeight = null;
        $now = now();

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                // Skip empty lines
                if (empty($row) || (count($row) === 1 && trim($row[0]) === '')) {
                    continue;
                }

                if ($hasFromWeight) {
                    $weightFrom = (float)str_replace(',', '', trim($row[0] ?? '0'));
                    $weightTo   = (float)str_replace(',', '', trim($row[1] ?? '0'));
                } else {
                    $weightTo   = (float)str_replace(',', '', trim($row[0] ?? '0'));
                    $weightFrom = $previousWeight;
                    $previousWeight = $weightTo;
                }

                if ($weightTo <= 0) {
                    continue;
                }

                if ($minWeight === null || $weightFrom < $minWeight) $minWeight = $weightFrom;
                if ($maxWeight === null || $weightTo > $maxWeight) $maxWeight = $weightTo;

                $totalSlabs++;

                // Determine rate type
                $rateType = 'flat';
                if ($rateTypeMode === 'all_per_kg') {
                    $rateType = 'per_kg';
                } elseif ($rateTypeMode === 'all_flat') {
                    $rateType = 'flat';
                } else {
                    // threshold mode
                    $rateType = ($weightFrom >= $perKgThreshold) ? 'per_kg' : 'flat';
                }

                // Process rates for each zone in this slab
                foreach ($zoneColumnMap as $colIndex => $zoneId) {
                    $rawCell = isset($row[$colIndex]) ? trim($row[$colIndex]) : '';
                    if ($rawCell === '' || $rawCell === '-' || !is_numeric(str_replace(',', '', $rawCell))) {
                        continue;
                    }

                    $rate = (float)str_replace(',', '', $rawCell);
                    $wFromRound = round($weightFrom, 2);
                    $wToRound   = round($weightTo, 2);
                    $dedupKey   = "{$servicePartnerId}_{$zoneId}_{$packageType}_{$wFromRound}_{$wToRound}_{$rateType}";

                    $recordsToInsert[$dedupKey] = [
                        'service_partner_id' => $servicePartnerId,
                        'zone_id'            => $zoneId,
                        'package_type'       => $packageType,
                        'weight_from'        => $wFromRound,
                        'weight_to'          => $wToRound,
                        'rate'               => $rate,
                        'rate_type'          => $rateType,
                        'currency'           => $currency,
                        'min_transit_days'   => 1,
                        'max_transit_days'   => 5,
                        'created_at'         => $now,
                        'updated_at'         => $now,
                    ];
                }
            }

            fclose($handle);

            if (empty($recordsToInsert)) {
                DB::rollBack();
                return ApiResponse::error('No valid rates found to import in the CSV file.');
            }

            // If overwrite mode, delete previous rates for this service partner and package type
            if ($mode === 'overwrite') {
                ServicePartnerZoneRate::where('service_partner_id', $servicePartnerId)
                    ->where('package_type', $packageType)
                    ->delete();
            }

            $uniqueRecords = array_values($recordsToInsert);

            // Insert / Upsert in chunks of 500
            foreach (array_chunk($uniqueRecords, 500) as $chunk) {
                ServicePartnerZoneRate::upsert(
                    $chunk,
                    ['service_partner_id', 'zone_id', 'package_type', 'weight_from', 'weight_to', 'rate_type'],
                    ['rate', 'currency', 'min_transit_days', 'max_transit_days', 'updated_at']
                );
            }

            DB::commit();

            $message = "Successfully uploaded rate sheet! Created " . count($uniqueRecords) . " rates across " . count($zoneColumnMap) . " zones for {$totalSlabs} weight slabs.";
            if (!empty($unmatchedHeaders)) {
                $message .= " (" . count($unmatchedHeaders) . " unrecognized zone column(s) skipped: " . implode(', ', $unmatchedHeaders) . ")";
            }
            if (!empty($skippedUnselectedHeaders)) {
                $message .= " (" . count($skippedUnselectedHeaders) . " unselected zone column(s) skipped: " . implode(', ', $skippedUnselectedHeaders) . ")";
            }

            return ApiResponse::success([
                'total_slabs'              => $totalSlabs,
                'total_rates_created'      => count($uniqueRecords),
                'zones_mapped'             => count($zoneColumnMap),
                'unmatched_columns'        => $unmatchedHeaders,
                'unmatched_count'          => count($unmatchedHeaders),
                'skipped_unselected_zones' => $skippedUnselectedHeaders,
                'skipped_unselected_count' => count($skippedUnselectedHeaders),
                'weight_from'              => $minWeight,
                'weight_to'                => $maxWeight,
            ], $message);
        } catch (\Throwable $e) {
            DB::rollBack();
            if (is_resource($handle)) {
                fclose($handle);
            }
            Log::error("Bulk rate upload error: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return ApiResponse::error('Failed to import rate matrix: ' . $e->getMessage());
        }
    }
    public function getServicePartnerZoneWiseRate(Request $request)
    {
        $servicePartnerZoneRates = $this->servicePartnerServices->getAllServicePartnerWithZoneRates($request->per_page ?? 20);
        return ApiResponse::success(ServicePartnerZoneWiserateResource::collection($servicePartnerZoneRates), "Service Partner Zone Rates fetched successfully!");
    }

    //Shipment Charges Master Functionality
    public function addShipmentChargesMaster(Request $request)
    {
        $validated = $request->validate(
            [
                'name' => 'required|string',
                'code' => 'required',
                'calculation_method' => 'required|string',
                'calculation_basis' => 'required|string',
                'status' => 'required|string',
                'description' => 'required|string',
            ],
            [
                'name.required' => 'Please enter shipment charges name.',
                'code.required' => 'Please enter shipment charges.',
                'calculation_method.required' => 'Please select calculation mehod.',
                'calculation_basis.required' => 'Please select calculation basis.',
                'description.required' => 'Please enter description',
            ]
        );
        try {
            DB::beginTransaction();
            $this->shipmentChargesMasterService->addShipmentChargeMaster($validated);
            DB::commit();
            return ApiResponse::success([], "Shipment Charges Master created successfully!");
        } catch (\Throwable $e) {
            DB::rollBack();
            return ApiResponse::error('Something went wrong!');
        }
    }

    public function deleteShipmentChargesMaster(int $id)
    {
        try {
            DB::beginTransaction();
            $this->shipmentChargesMasterService->deleteShipmentChargeMaster($id);
            DB::commit();
            return ApiResponse::success([], "Shipment Charges Master deleted successfully!");
        } catch (\Throwable $e) {
            DB::rollBack();
            return ApiResponse::error($e->getMessage());
        }
    }

    public function getAllShipmentChargeMaster()
    {
        $shipmentChargesMaster = $this->shipmentChargesMasterService->getAllShipmentChargeMaster();
        return ApiResponse::success($shipmentChargesMaster, "Shipment Charges Master fetched successfully!");
    }

    //mapped additional charges functionality
    public function addShipmentAdditionalCharge(Request $request)
    {
        $validated = $request->validate(
            [
                'service_partner_id' => 'required|integer',
                'charge_type_id' => 'required',
                'value' => 'required',
            ],
            [
                'service_partner_id.required' => 'Please select service partner.',
                'charge_type_id.required' => 'Please select charge type.',
                'value.required' => 'Please enter charges value.',
            ]
        );
        $validated['sort_order'] = $this->shipmentAdditionalChargeService->getNextSortOrder();
        try {
            DB::beginTransaction();
            $this->shipmentAdditionalChargeService->create($validated);
            DB::commit();
            return ApiResponse::success([], "Additional Charge mapped successfully!");
        } catch (\Throwable $e) {
            DB::rollBack();
            if ($e->getCode() == 1062) {
                return ApiResponse::error('Additional Charge already mapped!');
            }
            return ApiResponse::error('Something went wrong!');
        }
    }

    public function updateShipmentAdditionalCharge(Request $request, int $id)
    {
        $validated = $request->validate([
            'value'  => 'required|numeric|min:0',
            'status' => 'nullable|string|in:active,inactive',
        ]);
        try {
            DB::beginTransaction();
            $this->shipmentAdditionalChargeService->update($validated, $id);
            DB::commit();
            return ApiResponse::success([], "Additional Charge updated successfully!");
        } catch (\Throwable $e) {
            DB::rollBack();
            return ApiResponse::error([], 'Failed to update additional charge: ' . $e->getMessage());
        }
    }

    public function bulkAddShipmentAdditionalCharges(Request $request)
    {
        $request->validate([
            'service_partner_id'    => 'nullable|integer|exists:servicepartner,id',
            'service_partner_ids'   => 'nullable|array',
            'service_partner_ids.*' => 'integer|exists:servicepartner,id',
            'charges'               => 'required|array|min:1',
            'charges.*.charge_type_id' => 'required|integer|exists:shipment_charges_master,id',
            'charges.*.value'       => 'required|numeric|min:0',
            'charges.*.status'      => 'nullable|string|in:active,inactive',
            'mode'                  => 'nullable|string|in:upsert,skip,overwrite',
        ], [
            'charges.required'      => 'Please provide at least one charge to map.',
            'charges.min'           => 'Please select at least one charge to map.',
        ]);

        $partnerIds = [];
        if ($request->has('service_partner_ids') && is_array($request->input('service_partner_ids'))) {
            $partnerIds = array_values(array_filter(array_map('intval', $request->input('service_partner_ids'))));
        } elseif ($request->filled('service_partner_id')) {
            $partnerIds = [(int)$request->input('service_partner_id')];
        }

        if (empty($partnerIds)) {
            return ApiResponse::error([], 'Please select at least one service partner.');
        }

        $charges = $request->input('charges');
        $mode = $request->input('mode', 'upsert');

        $insertedCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;

        DB::beginTransaction();
        try {
            $nextSortOrder = $this->shipmentAdditionalChargeService->getNextSortOrder();

            foreach ($partnerIds as $partnerId) {
                if ($mode === 'overwrite') {
                    ShipmentAdditionalCharges::where('service_partner_id', $partnerId)->delete();
                }

                foreach ($charges as $c) {
                    $chargeTypeId = (int)$c['charge_type_id'];
                    $value = (float)$c['value'];
                    $status = isset($c['status']) && in_array(strtolower($c['status']), ['active', 'inactive'])
                        ? strtolower($c['status'])
                        : 'active';

                    $existing = ShipmentAdditionalCharges::where('service_partner_id', $partnerId)
                        ->where('charge_type_id', $chargeTypeId)
                        ->first();

                    if ($existing) {
                        if ($mode === 'skip') {
                            $skippedCount++;
                            continue;
                        }
                        $existing->value = $value;
                        $existing->status = $status;
                        $existing->save();
                        $updatedCount++;
                    } else {
                        ShipmentAdditionalCharges::create([
                            'service_partner_id' => $partnerId,
                            'charge_type_id'     => $chargeTypeId,
                            'value'              => $value,
                            'status'             => $status,
                            'sort_order'         => $nextSortOrder++,
                        ]);
                        $insertedCount++;
                    }
                }
            }

            DB::commit();

            $partnerCount = count($partnerIds);
            return ApiResponse::success([
                'partner_count'  => $partnerCount,
                'inserted_count' => $insertedCount,
                'updated_count'  => $updatedCount,
                'skipped_count'  => $skippedCount,
                'total_affected' => $insertedCount + $updatedCount,
            ], "Successfully saved additional charges for {$partnerCount} partner(s) ({$insertedCount} created, {$updatedCount} updated).");
        } catch (\Throwable $e) {
            DB::rollBack();
            return ApiResponse::error([], 'Error bulk adding additional charges: ' . $e->getMessage());
        }
    }

    public function bulkUploadShipmentAdditionalCharges(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240',
            'mode' => 'nullable|string|in:upsert,overwrite,skip',
        ], [
            'file.required' => 'Please select a CSV file to upload.',
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, ['csv', 'txt'])) {
            return ApiResponse::error([], 'Invalid file format. Please upload a .csv file.');
        }

        $mode = $request->input('mode', 'upsert');

        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            return ApiResponse::error([], 'Failed to open the uploaded file.');
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            return ApiResponse::error([], 'The uploaded CSV file is empty.');
        }

        // Strip BOM
        $bom = pack('H*', 'EFBBBF');
        $header[0] = preg_replace("/^$bom/", '', $header[0]);
        $cleanedHeaders = array_map(fn($h) => strtolower(trim($h)), $header);

        // Preload Service Partners
        $partners = ServicePartner::all();
        $partnersByName = [];
        $partnersByCode = [];
        $partnersById = [];
        foreach ($partners as $p) {
            $partnersByName[strtolower(trim($p->name))] = $p;
            $partnersByCode[strtolower(trim($p->service_code ?? ''))] = $p;
            $partnersById[$p->id] = $p;
        }

        // Preload Shipment Charges Master
        $chargeMasters = ShipmentChargesMaster::all();
        $chargesByName = [];
        $chargesByCode = [];
        $chargesById = [];
        foreach ($chargeMasters as $cm) {
            $chargesByName[strtolower(trim($cm->name))] = $cm;
            $chargesByCode[strtolower(trim($cm->code))] = $cm;
            $chargesById[$cm->id] = $cm;
        }

        $insertedCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;
        $unmatchedRows = [];
        $totalRows = 0;
        $nextSortOrder = $this->shipmentAdditionalChargeService->getNextSortOrder();

        // Check if format is Matrix or Row-by-Row
        $firstCol = $cleanedHeaders[0] ?? '';
        $secondCol = $cleanedHeaders[1] ?? '';

        // If the second column matches an actual charge name or code from master, it's Matrix format.
        // Otherwise, it's standard Row-by-Row format (Partner, Charge Type, Value, Status).
        $isRowByRow = true;
        if (isset($chargesByName[$secondCol]) || isset($chargesByCode[$secondCol])) {
            $isRowByRow = false;
        }

        DB::beginTransaction();
        try {
            if ($isRowByRow) {
                // Row-by-row format: Partner, Charge Type/Code, Value, Status
                $partnerColIdx = 0;
                $chargeColIdx = 1;
                $valueColIdx = 2;
                $statusColIdx = 3;

                foreach ($cleanedHeaders as $idx => $h) {
                    if (str_contains($h, 'partner') || str_contains($h, 'courier')) $partnerColIdx = $idx;
                    elseif (str_contains($h, 'charge') || str_contains($h, 'type')) $chargeColIdx = $idx;
                    elseif (str_contains($h, 'value') || str_contains($h, 'rate') || str_contains($h, 'amount')) $valueColIdx = $idx;
                    elseif (str_contains($h, 'status')) $statusColIdx = $idx;
                }

                $lineNo = 1;
                while (($row = fgetcsv($handle)) !== false) {
                    $lineNo++;
                    if (empty($row) || (count($row) === 1 && trim($row[0]) === '')) continue;
                    $totalRows++;

                    $rawPartner = trim($row[$partnerColIdx] ?? '');
                    $rawCharge  = trim($row[$chargeColIdx] ?? '');
                    $rawValue   = trim($row[$valueColIdx] ?? '');
                    $rawStatus  = trim($row[$statusColIdx] ?? 'active');

                    if ($rawPartner === '' || $rawCharge === '' || $rawValue === '') {
                        $unmatchedRows[] = "Row {$lineNo}: Missing partner, charge type, or value.";
                        continue;
                    }

                    // Resolve partner
                    $partnerKey = strtolower($rawPartner);
                    $matchedPartner = $partnersByName[$partnerKey] ?? $partnersByCode[$partnerKey] ?? (is_numeric($rawPartner) ? ($partnersById[(int)$rawPartner] ?? null) : null);

                    if (!$matchedPartner) {
                        $unmatchedRows[] = "Row {$lineNo}: Service partner '{$rawPartner}' not found.";
                        continue;
                    }

                    // Resolve charge
                    $chargeKey = strtolower($rawCharge);
                    $matchedCharge = $chargesByName[$chargeKey] ?? $chargesByCode[$chargeKey] ?? (is_numeric($rawCharge) ? ($chargesById[(int)$rawCharge] ?? null) : null);

                    if (!$matchedCharge) {
                        $unmatchedRows[] = "Row {$lineNo}: Charge type '{$rawCharge}' not found.";
                        continue;
                    }

                    $cleanVal = str_replace(['%', ',', ' '], '', $rawValue);
                    if (!is_numeric($cleanVal)) {
                        $unmatchedRows[] = "Row {$lineNo}: Invalid value '{$rawValue}'.";
                        continue;
                    }

                    $value = (float)$cleanVal;
                    $status = strtolower($rawStatus) === 'inactive' ? 'inactive' : 'active';

                    $existing = ShipmentAdditionalCharges::where('service_partner_id', $matchedPartner->id)
                        ->where('charge_type_id', $matchedCharge->id)
                        ->first();

                    if ($existing) {
                        if ($mode === 'skip') {
                            $skippedCount++;
                            continue;
                        }
                        $existing->value = $value;
                        $existing->status = $status;
                        $existing->save();
                        $updatedCount++;
                    } else {
                        ShipmentAdditionalCharges::create([
                            'service_partner_id' => $matchedPartner->id,
                            'charge_type_id'     => $matchedCharge->id,
                            'value'              => $value,
                            'status'             => $status,
                            'sort_order'         => $nextSortOrder++,
                        ]);
                        $insertedCount++;
                    }
                }
            } else {
                // Matrix format: Partner, Fuel Surcharge, GST, Remote Area Charge, ...
                $chargeColumnMap = [];
                for ($i = 1; $i < count($header); $i++) {
                    $rawHeader = trim($header[$i]);
                    if ($rawHeader === '') continue;
                    $key = strtolower($rawHeader);
                    $matched = $chargesByName[$key] ?? $chargesByCode[$key] ?? (is_numeric($rawHeader) ? ($chargesById[(int)$rawHeader] ?? null) : null);
                    if ($matched) {
                        $chargeColumnMap[$i] = $matched;
                    }
                }

                if (empty($chargeColumnMap)) {
                    fclose($handle);
                    DB::rollBack();
                    return ApiResponse::error([], 'No recognized charge column headers found in CSV.');
                }

                $lineNo = 1;
                while (($row = fgetcsv($handle)) !== false) {
                    $lineNo++;
                    if (empty($row) || (count($row) === 1 && trim($row[0]) === '')) continue;
                    $totalRows++;

                    $rawPartner = trim($row[0] ?? '');
                    if ($rawPartner === '') continue;

                    $partnerKey = strtolower($rawPartner);
                    $matchedPartner = $partnersByName[$partnerKey] ?? $partnersByCode[$partnerKey] ?? (is_numeric($rawPartner) ? ($partnersById[(int)$rawPartner] ?? null) : null);

                    if (!$matchedPartner) {
                        $unmatchedRows[] = "Row {$lineNo}: Service partner '{$rawPartner}' not found.";
                        continue;
                    }

                    foreach ($chargeColumnMap as $colIdx => $chargeMaster) {
                        $rawCell = isset($row[$colIdx]) ? trim($row[$colIdx]) : '';
                        if ($rawCell === '' || $rawCell === '-') continue;

                        $cleanVal = str_replace(['%', ',', ' '], '', $rawCell);
                        if (!is_numeric($cleanVal)) continue;

                        $value = (float)$cleanVal;
                        $existing = ShipmentAdditionalCharges::where('service_partner_id', $matchedPartner->id)
                            ->where('charge_type_id', $chargeMaster->id)
                            ->first();

                        if ($existing) {
                            if ($mode === 'skip') {
                                $skippedCount++;
                                continue;
                            }
                            $existing->value = $value;
                            $existing->save();
                            $updatedCount++;
                        } else {
                            ShipmentAdditionalCharges::create([
                                'service_partner_id' => $matchedPartner->id,
                                'charge_type_id'     => $chargeMaster->id,
                                'value'              => $value,
                                'status'             => 'active',
                                'sort_order'         => $nextSortOrder++,
                            ]);
                            $insertedCount++;
                        }
                    }
                }
            }

            fclose($handle);
            DB::commit();

            return ApiResponse::success([
                'total_rows'      => $totalRows,
                'inserted_count'  => $insertedCount,
                'updated_count'   => $updatedCount,
                'skipped_count'   => $skippedCount,
                'unmatched_rows'  => $unmatchedRows,
                'unmatched_count' => count($unmatchedRows),
            ], "Bulk upload complete! Created: {$insertedCount}, Updated: {$updatedCount}, Skipped: {$skippedCount}.");
        } catch (\Throwable $e) {
            fclose($handle);
            DB::rollBack();
            return ApiResponse::error([], 'Error during bulk upload: ' . $e->getMessage());
        }
    }

    public function deleteShipmentAdditionalCharge(int $id)
    {
        try {
            DB::beginTransaction();
            $this->shipmentAdditionalChargeService->delete($id);
            DB::commit();
            return ApiResponse::success([], "Additional Charge deleted successfully!");
        } catch (\Throwable $e) {
            DB::rollBack();
            return ApiResponse::error('Something went wrong!');
        }
    }

    public function getServicePartnerAdditionalCharges()
    {
        $servicePartnerAdditionalCharges = $this->shipmentAdditionalChargeService->getAll();
        return ApiResponse::success(ShipmentAdditionalChargesResource::collection($servicePartnerAdditionalCharges), "Service Partner Additional Charges fetched successfully!");
    }

    public function getHSNCode() {}
}
