<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServicePartnerZoneWiserateResource extends JsonResource
{
    // SELECT sp.name,spr.* FROM `service_partner_rate_slab` spr INNER JOIN servicepartner sp on sp.id =spr.service_partner_id WHERE spr.zone_id in (15,17,1) and spr.package_type= 'nondoc' and spr.weight_from>=0.000 AND spr.weight_to <=0.500;
    public function toArray(Request $request): array
    {
        $zones = [];             // zoneId => ['id' => ..., 'name' => ...]
        $slabsByPackage = [];    // packageType => ["from-to" => ['weight_from'=>, 'weight_to'=>, 'rates' => [zoneId => rate]]]
        $packageTypes = [];      // set of package types seen
        $rateType = null;

        if ($this->relationLoaded('zoneRates')) {
            foreach ($this->zoneRates as $rate) {
                $zoneId = $rate->zone_id;

                if (!isset($zones[$zoneId])) {
                    $zones[$zoneId] = [
                        'id'   => $rate->zone->id ?? null,
                        'name' => $rate->zone->zone_name ?? null,
                    ];
                }

                $packageType = $rate->package_type;
                $packageTypes[$packageType] = true;

                $weightKey = $rate->weight_from . '-' . $rate->weight_to;

                if (!isset($slabsByPackage[$packageType][$weightKey])) {
                    $slabsByPackage[$packageType][$weightKey] = [
                        'weight_from' => $rate->weight_from,
                        'weight_to'   => $rate->weight_to,
                        'rate_type'   => $rate->rate_type,
                        'rates'       => [],
                    ];
                }

                $slabsByPackage[$packageType][$weightKey]['rates'][$zoneId] = $rate->rate;

                $rateType = $rate->rate_type;
            }
        }

        // sort zones by name (natural order)
        $zonesList = array_values($zones);
        usort($zonesList, fn($a, $b) => strnatcmp($a['name'] ?? '', $b['name'] ?? ''));

        // sort each package type's slab rows by weight_from
        foreach ($slabsByPackage as $type => $rows) {
            $rowsList = array_values($rows);
            usort($rowsList, fn($a, $b) => $a['weight_from'] <=> $b['weight_from']);
            $slabsByPackage[$type] = $rowsList;
        }

        $packages = [];
        foreach ($packageTypes as $pkgType => $true) {
            $pkgRateType = null;
            if ($this->relationLoaded('zoneRates')) {
                foreach ($this->zoneRates as $rate) {
                    if ($rate->package_type === $pkgType) {
                        $pkgRateType = $rate->rate_type;
                        break;
                    }
                }
            }

            $rows = [];
            $slabRows = $slabsByPackage[$pkgType] ?? [];
            foreach ($slabRows as $slab) {
                $zoneRatesArray = [];
                $zoneNamesArray = [];
                foreach ($zonesList as $z) {
                    $rate = $slab['rates'][$z['id']] ?? '';
                    // optionally format the rate with commas, or just cast to string
                    $zoneRatesArray[] = $rate !== '' ? (string)(float)$rate : '-';
                    $zoneNamesArray[] = $z['name'];
                }

                $rows[] = [
                    'weight_from' => $slab['weight_from'],
                    'weight_to' => $slab['weight_to'],
                    'rate_type' => $slab['rate_type'],
                    'zones'  => array_combine($zoneNamesArray, $zoneRatesArray),
                ];
            }

            $packages[]  = [
                'id'          => (string)$this->id,
                'service'     => $this->name,
                'packageType' => strtoupper($pkgType),
                'rateType'    => ucfirst($pkgRateType ?? 'Slab'),
                'zoneCount'   => count($zonesList),
                'status'      => 'Active',
                'rows'        => $rows,
            ];
        }

        // We return the array of packages. 
        // Note: Because this is a resource for a single ServicePartner, 
        // this will return an array of packages.
        return $packages;
    }
}
