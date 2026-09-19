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

    public function calculateRate(Request $request)
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
                'package_type' => 'required',
                'boxes' => 'required|array',
                'boxes.*.actWt' => 'required|numeric',
                'boxes.*.length' => 'required|numeric',
                'boxes.*.width' => 'required|numeric',
                'boxes.*.height' => 'required|numeric',
            ],
            [
                'destination.required' => 'Please select destination',
                'package_type.required' => 'Please select package type',
                'boxes.required' => 'Please enter dimensions of box',
                'boxes.*.actWt.required' => 'Please enter actual weight',
                'boxes.*.length.required' => 'Please enter length',
                'boxes.*.width.required' => 'Please enter width',
                'boxes.*.height.required' => 'Please enter height',
                'boxes.*.actWt.numeric' => 'Actual weight must be numeric',
                'boxes.*.length.numeric' => 'Length must be numeric',
                'boxes.*.width.numeric' => 'Width must be numeric',
                'boxes.*.height.numeric' => 'Height must be numeric',
            ]
        );
        $formData   = [
            'country_id' => $request->destination,
            'package_type' => strtolower($request->package_type),
            'dimensions' => $request->boxes
        ];
        $rates = $this->rateCalService->calculateRate($formData);
        return ApiResponse::success($rates, 'Rate fetched successfully', 200);
    }
}
