<?php

use App\Enum\Incoterm;
use Illuminate\Support\Facades\Route;

Route::get('/test', function () {
    return Incoterm::options(); //implementation of enum testing
});

Route::get('/', function () {
    return view('welcome');
});
