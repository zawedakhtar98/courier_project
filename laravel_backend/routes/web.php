<?php

use App\Enum\Incoterm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/test', function (Request $request) {
    $tracking_no = $request->input('tracking_no');
    // echo $tracking_no;
    // die;
    $curl = curl_init();
    $postData = array(
        "Token" => "KSdhnw87ftcao;ex/m21po47mph",
        "TrackingNo" => $tracking_no
    );

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://webapi.expressits.in/api/Client/TrackAwbNo',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($postData),
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Cache-Control: no-cache'
        ),
    ));

    $response = curl_exec($curl);
    // echo $response;
    $curlError = curl_error($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    // die;
    curl_close($curl);
    return response()->json([
        'tracking_no' => $tracking_no,
        'http_code' => $httpCode,
        'curl_error' => $curlError,
        'raw_response' => $response,
        'decoded_response' => json_decode($response, true),
    ]);
});

Route::get('/', function () {
    return view('welcome');
});
