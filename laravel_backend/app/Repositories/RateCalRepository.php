<?php

namespace App\Repositories;

use App\Models\ServicePartnerZoneRate;
use App\Models\ShipmentAdditionalCharges;
use App\Models\ZoneCountryMapping;
use App\Repositories\Interface\RateCalRepositoryInterface;
use Illuminate\Support\Facades\Log;

class RateCalRepository implements RateCalRepositoryInterface
{
    public function calculateRate(array $condition)
    {
        Log::info("condition from rateCalRepository at " . __LINE__, $condition);

        $countryId = $condition['destination'] ?? null;
        $packageType = $condition['package_type'] ?? null;
        $dimensions = $condition['dimensions'] ?? [];

        if (!$countryId || !$packageType || empty($dimensions)) {
            return [];
        }

        $actualWt = 0.0;
        $totalVolumeWt = 0.0;
        $billableWt = 0.0;
        $totalBoxes = count($dimensions);

        foreach ($dimensions as $dimension) {
            $act = (float)($dimension['actWt'] ?? $dimension['actual_weight'] ?? $dimension['weight'] ?? 0);
            $l = (float)($dimension['length'] ?? $dimension['l'] ?? 0);
            $w = (float)($dimension['width'] ?? $dimension['w'] ?? 0);
            $h = (float)($dimension['height'] ?? $dimension['h'] ?? 0);
            $vol = ($l * $w * $h) / 5000;

            $actualWt += $act;
            $totalVolumeWt += $vol;
            $billableWt += max($act, $vol);
        }

        // If actual_weight or chargeable_weight are passed via API, prioritize them
        if (isset($condition['actual_weight']) && (float)$condition['actual_weight'] > 0) {
            $actualWt = (float)$condition['actual_weight'];
        }
        if (isset($condition['chargeable_weight']) && (float)$condition['chargeable_weight'] > 0) {
            $billableWt = (float)$condition['chargeable_weight'];
        } elseif (isset($condition['chargeable_weight']) && (float)$condition['chargeable_weight'] > 0) {
            $billableWt = (float)$condition['s'];
        }

        $actualWt = round($actualWt, 2);
        $totalVolumeWt = round($totalVolumeWt, 2);
        $billableWt = round($billableWt, 2);

        // Get zone IDs mapped to the selected country
        $zoneIds = ZoneCountryMapping::where('country_id', $countryId)->pluck('zone_id');

        if ($zoneIds->isEmpty()) {
            return [];
        }

        $rates = ServicePartnerZoneRate::with(['servicePartner', 'zone'])
            ->whereIn('zone_id', $zoneIds)
            ->where('package_type', $packageType)
            ->where('weight_from', '<=', $billableWt)
            ->where('weight_to', '>=', $billableWt)
            ->get();

        // Deduplicate per service partner if boundary weight matches multiple slabs (e.g. 0.00-0.50 vs 0.50-1.00)
        $uniqueRates = [];
        foreach ($rates as $rate) {
            $spId = $rate->service_partner_id;
            if (!isset($uniqueRates[$spId])) {
                $uniqueRates[$spId] = $rate;
            } else {
                if ($billableWt > 0 && (float)$rate->weight_from < $billableWt && (float)$rate->weight_to >= $billableWt) {
                    $uniqueRates[$spId] = $rate;
                }
            }
        }
        $rates = array_values($uniqueRates);

        $result = [];

        foreach ($rates as $rate) {
            // Calculate base freight
            if ($rate->rate_type === 'per_kg') {
                $baseFreight = round((float)$rate->rate * $billableWt, 2);
            } else {
                $baseFreight = round((float)$rate->rate, 2);
            }

            // Calculate additional charges with bifurcation based on mapping
            $chargesCalculation = $this->calculateAdditionalCharges(
                servicePartnerId: (int)$rate->service_partner_id,
                baseFreight: $baseFreight,
                chargeableWeight: $billableWt,
                actualWeight: $actualWt,
                totalBoxes: $totalBoxes
            );

            $partner = $rate->servicePartner;
            $logoUrl = ($partner && $partner->logo)
                ? asset('service_partner_logo/' . ltrim($partner->logo, '/'))
                : '';

            $finalTotal = $chargesCalculation['final_total'];
            $subTotal = $chargesCalculation['sub_total'];
            $perKgRate = $billableWt > 0 ? round($finalTotal / $billableWt, 2) : 0;

            $result[] = [
                'id' => $rate->id,
                'service_partner_id' => $rate->service_partner_id,
                'zone_id' => $rate->zone_id,
                'zone_name' => $rate->zone?->zone_name,
                'package_type' => $rate->package_type,
                'weight_from' => (float)$rate->weight_from,
                'weight_to' => (float)$rate->weight_to,
                'rate' => (float)$rate->rate,
                'rate_type' => $rate->rate_type,
                'currency' => $rate->currency ?? 'INR',
                'min_transit_days' => $rate->min_transit_days,
                'max_transit_days' => $rate->max_transit_days,

                // Weights & boxes
                'actual_weight' => $actualWt,
                'volumetric_weight' => $totalVolumeWt,
                'chargeable_weight' => $billableWt,
                'total_boxes' => $totalBoxes,

                // Financial bifurcation
                'base_freight' => $baseFreight,
                'sub_total' => $subTotal,
                'grand_total' => $finalTotal,
                'final_total' => $finalTotal,
                'total_amount' => $finalTotal,
                'per_kg_rate' => $perKgRate,
                'additional_charges' => $chargesCalculation['breakdown'],
                'total_additional_charges' => $chargesCalculation['total_additional_charges'],

                // Service partner details
                'service_partner' => $partner ? [
                    'id' => $partner->id,
                    'name' => ucfirst($partner->name),
                    'service_code' => $partner->service_code,
                    'logo' => $logoUrl,
                    'status' => $partner->status,
                ] : null,
            ];
        }

        return $result;
    }

    /**
     * Calculate additional charges with bifurcation for a service partner.
     *
     * Supports:
     * - per_shipment: Flat amount for the entire shipment
     * - per_box: Flat amount × number of boxes
     * - chargeable_weight: Rate × chargeable weight
     * - actual_weight: Rate × actual scale weight
     * - freight: % of base freight only
     * - shipment_total / running_total: % of running total (freight + charges with lower sort order)
     * - shipment_value: % of base freight (selected based on chargeable weight)
     * - sub_total: % of everything before tax (the running subtotal)
     */
    protected function calculateAdditionalCharges(
        int $servicePartnerId,
        float $baseFreight,
        float $chargeableWeight,
        float $actualWeight,
        int $totalBoxes
    ): array {
        $charges = ShipmentAdditionalCharges::with('shipmentChargeType')
            ->where('service_partner_id', $servicePartnerId)
            ->where('status', 'active')
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $breakdown = [];
        $runningTotal = $baseFreight;
        $totalAdditionalCharges = 0.0;
        $subTotal = null;

        foreach ($charges as $charge) {
            $master = $charge->shipmentChargeType;
            if (!$master || $master->status !== 'active') {
                continue;
            }

            $rateValue = (float)$charge->value;
            $basis = strtolower(trim($master->calculation_basis ?? ''));
            $method = strtolower(trim($master->calculation_method ?? ''));
            $amount = 0.0;

            // Track sub_total before any tax / sub_total-based charge (e.g. GST)
            if ($basis === 'sub_total' || strcasecmp($master->code ?? '', 'GST') === 0 || strcasecmp($master->name ?? '', 'GST') === 0) {
                if ($subTotal === null) {
                    $subTotal = $runningTotal;
                }
            }

            switch ($basis) {
                case 'per_shipment':
                    // One fixed amount for the entire shipment
                    $amount = $rateValue;
                    break;

                case 'per_box':
                    // Fixed amount × number of boxes
                    $amount = $rateValue * $totalBoxes;
                    break;

                case 'chargeable_weight':
                case 'weight':
                    // Rate × the higher of actual and volumetric weight
                    $amount = $rateValue * $chargeableWeight;
                    break;

                case 'actual_weight':
                    // Rate × real weight on the scale
                    $amount = $rateValue * $actualWeight;
                    break;

                case 'freight':
                    // % of base freight only
                    $amount = ($baseFreight * $rateValue) / 100;
                    break;

                case 'shipment_total':
                case 'running_total':
                    // % of freight plus all charges with smaller sort_order applied so far
                    $amount = ($runningTotal * $rateValue) / 100;
                    break;

                case 'shipment_value':
                    // % of base freight selected from weight slab
                    $amount = ($baseFreight * $rateValue) / 100;
                    break;

                case 'sub_total':
                    // % of everything before tax (the running subtotal)
                    $amount = ($runningTotal * $rateValue) / 100;
                    break;

                default:
                    if ($method === 'percentage') {
                        $amount = ($runningTotal * $rateValue) / 100;
                    } elseif ($method === 'per_kg') {
                        $amount = $rateValue * $chargeableWeight;
                    } else {
                        $amount = $rateValue;
                    }
                    break;
            }

            $amount = round($amount, 2);
            $runningTotal += $amount;
            $totalAdditionalCharges += $amount;

            $breakdown[] = [
                'charge_id' => $charge->id,
                'charge_type_id' => $master->id,
                'name' => $master->name,
                'code' => $master->code,
                'description' => $master->description,
                'calculation_method' => $master->calculation_method,
                'calculation_basis' => $master->calculation_basis,
                'rate_value' => $rateValue,
                'amount' => $amount,
            ];
        }

        if ($subTotal === null) {
            $subTotal = $runningTotal;
        }

        return [
            'breakdown' => $breakdown,
            'sub_total' => round($subTotal, 2),
            'total_additional_charges' => round($totalAdditionalCharges, 2),
            'final_total' => round($runningTotal, 2),
        ];
    }
}
