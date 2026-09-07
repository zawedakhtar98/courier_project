<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/users', [App\Http\Controllers\API\AuthController::class, 'index']);
Route::post('/get-user-by-email', [App\Http\Controllers\API\AuthController::class, 'findByEmail']);


Route::post('/login', [App\Http\Controllers\API\AuthController::class, 'login']);
Route::post('/register', [App\Http\Controllers\API\AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/login-user', [App\Http\Controllers\API\AuthController::class, 'getLoginUser']);
    Route::post('/logout', [App\Http\Controllers\API\AuthController::class, 'logout']);
});
