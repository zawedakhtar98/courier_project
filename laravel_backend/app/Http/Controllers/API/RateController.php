<?php

namespace App\Http\Controllers\API;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Services\RateCalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RateController extends Controller
{
    public function __construct(protected RateCalService $rateCalService) {}

    public function shipmentRateCalculator(Request $request)
    {
        // {
        //     "destination": 2,
        //     "package_type": "NONDOC",
        //     "boxes": [
        //         {
        //             "actWt": 2,
        //             "length": 20,
        //             "width": 20,
        //             "height": 20,
        //         },
        //         {
        //             "actWt": 2,
        //             "length": 20,
        //             "width": 20,
        //             "height": 40,
        //         }
        //     ]
        // }
        $request->validate(
            [
                'destination' => 'required',
                'package_type' => 'required|string',
                'boxes' => 'required|array',
                'chargeable_weight' => 'nullable|numeric',
                'actual_weight' => 'nullable|numeric',
            ],
            [
                'destination.required' => 'Please select destination country',
                'package_type.required' => 'Please select package type',
                'boxes.required' => 'Please enter dimensions',
            ]
        );

        $formData = [
            'destination' => $request->destination,
            'package_type' => strtolower($request->package_type),
            'dimensions' => $request->boxes ?? [],
            'chargeable_weight' => (float)($request->chargeable_weight ?? 0),
            'actual_weight' => (float)($request->actual_weight ?? 0),
        ];

        $rates = $this->rateCalService->calculateRate($formData);
        return ApiResponse::success($rates, 'Rate fetched successfully', 200);
    }
}
