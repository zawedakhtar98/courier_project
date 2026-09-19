<?php

namespace App\Repositories;

use App\Models\ServicePartnerZoneRate;
use App\Models\ZoneCountryMapping;

class RateCalRepository
{

    public function calculateRate(array $condition)
    {
        //  get rate based on condition (Implemented based selected detination,[selected country, package_type, actual_weight, dimension_weight])

        $countryId = $condition['country_id'] ?? null;
        $packageType = $condition['package_type'] ?? null;
        $dimensions = $condition['dimensions'] ?? [];

        if (!$countryId || !$packageType) {
            return [];
        }

        $billableWt  = 0;

        foreach ($dimensions as $dimension) {
            $volumeWt = ($dimension['length'] * $dimension['width'] * $dimension['height']) / 5000;
            $billableWt += max($dimension['actWt'], $volumeWt);
        }

        //get zone id based on country id
        $zoneIds = ZoneCountryMapping::where('country_id', $countryId)->pluck('zone_id');

        if ($zoneIds->isNotEmpty()) {
            $rates = ServicePartnerZoneRate::with('servicePartner')
                ->whereIn('zone_id', $zoneIds)
                ->where('package_type', $packageType)
                ->where('weight_from', '<=', $billableWt)
                ->where('weight_to', '>=', $billableWt)
                ->get();
            return $rates;
        }
        return [];
    }
}
