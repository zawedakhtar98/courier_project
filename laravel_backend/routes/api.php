<?php

use App\Http\Controllers\API\AdminController;
use Illuminate\Support\Facades\Route;

Route::get('/users', [App\Http\Controllers\API\AuthController::class, 'index']);
Route::post('/get-user-by-email', [App\Http\Controllers\API\AuthController::class, 'findByEmail']);


Route::post('/login', [App\Http\Controllers\API\AuthController::class, 'login']);
Route::post('/register', [App\Http\Controllers\API\AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/login-user', [App\Http\Controllers\API\AuthController::class, 'getLoginUser']);
    Route::post('/logout', [App\Http\Controllers\API\AuthController::class, 'logout']);

    Route::prefix('admin')->group(function () {

        //service partner
        Route::prefix('service-partner')->group(function () {
            Route::post('add-new', [AdminController::class, 'addNewServicePartner']);
            Route::put('update/{id}', [AdminController::class, 'updateServicePartner']);
            Route::get('getAll-partners', [AdminController::class, 'getAllServicePartners']);
        });
        Route::post('/shipments/save', [AdminController::class, 'saveShipment']);

        //country Master
        Route::prefix('country')->group(function () {
            Route::post('add', [AdminController::class, 'addCountries']);
            Route::put('update/{id}', [AdminController::class, 'updateCountry']);
            Route::get('get-list', [AdminController::class, 'getCountryList']);
            Route::delete('delete/{id}', [AdminController::class, 'deleteCountry']);
            Route::put('update-status/{id}', [AdminController::class, 'updateCountryStatus']);
        });

        //Zone Master
        Route::prefix('zone')->group(function () {
            Route::post('add', [AdminController::class, 'addZone']);
            Route::put('update/{id}', [AdminController::class, 'updateZone']);
            Route::get('get-list', [AdminController::class, 'getZoneList']);
            Route::post('map-countries', [AdminController::class, 'addZoneCountries']);
        });
    });
});
