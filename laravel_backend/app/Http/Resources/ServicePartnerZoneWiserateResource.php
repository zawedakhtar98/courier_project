<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServicePartnerZoneWiserateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    // public function toArray(Request $request): array
    // {
    //     $zones = [];

    //     if ($this->relationLoaded('zoneRates')) {
    //         foreach ($this->zoneRates as $rate) {
    //             $zoneId = $rate->zone_id;
    //             if (!isset($zones[$zoneId])) {
    //                 $zones[$zoneId] = [

    //                     'id' => $rate->zone->id ?? null,
    //                     'name' => $rate->zone->zone_name ?? null,
    //                     'rates' => []

    //                 ];
    //             }

    //             $zones[$zoneId]['rates'][] = [
    //                 'id' => $rate->id,
    //                 'package_type' => $rate->package_type,
    //                 'weight_from' => $rate->weight_from,
    //                 'weight_to' => $rate->weight_to,
    //                 'rate' => $rate->rate,
    //                 'rate_type' => $rate->rate_type,
    //                 'currency' => $rate->currency,
    //                 'max_transit_days' => $rate->max_transit_days,
    //                 'min_transit_days' => $rate->min_transit_days,
    //             ];
    //         }
    //     }

    //     return [
    //         'servicePartner' => [
    //             'id' => $this->id,
    //             'name' => $this->name,
    //             'zones' => array_values($zones)
    //         ],
    //     ];
    // }

    // public function toArray(Request $request): array
    // {
    //     $zones = [];        // zoneId => ['id' => ..., 'name' => ...]
    //     $slabs = [];         // "from-to" => ['weight_from'=>, 'weight_to'=>, 'rates' => [zoneId => rate]]
    //     $packageTypes = [];  // set of package types seen
    //     $rateType = null;

    //     if ($this->relationLoaded('zoneRates')) {
    //         foreach ($this->zoneRates as $rate) {
    //             $zoneId = $rate->zone_id;

    //             if (!isset($zones[$zoneId])) {
    //                 $zones[$zoneId] = [
    //                     'id'   => $rate->zone->id ?? null,
    //                     'name' => $rate->zone->zone_name ?? null,
    //                 ];
    //             }

    //             $slabKey = $rate->weight_from . '-' . $rate->weight_to;

    //             if (!isset($slabs[$slabKey])) {
    //                 $slabs[$slabKey] = [
    //                     'weight_from' => $rate->weight_from,
    //                     'weight_to'   => $rate->weight_to,
    //                     'rates'       => [],
    //                 ];
    //             }

    //             $slabs[$slabKey]['rates'][$zoneId] = $rate->rate;

    //             $packageTypes[$rate->package_type] = true;
    //             $rateType = $rate->rate_type; // assumes one rate_type per partner
    //         }
    //     }

    //     // sort zones by name (natural order, so Zone_9 < Zone_10)
    //     $zonesList = array_values($zones);
    //     usort($zonesList, fn($a, $b) => strnatcmp($a['name'] ?? '', $b['name'] ?? ''));

    //     // sort slabs by weight_from
    //     $slabRows = array_values($slabs);
    //     usort($slabRows, fn($a, $b) => $a['weight_from'] <=> $b['weight_from']);

    //     return [
    //         'servicePartner' => [
    //             'id'            => $this->id,
    //             'name'          => $this->name,
    //             'package_types' => array_keys($packageTypes),
    //             'rate_type'     => $rateType,
    //             'zones'         => $zonesList,
    //             'slab_rows'     => $slabRows,
    //         ],
    //     ];
    // }

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
