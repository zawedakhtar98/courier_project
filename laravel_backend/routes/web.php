<?php

use App\Enum\Incoterm;
use Illuminate\Support\Facades\Route;

Route::get('/test', function () {
    return Incoterm::options();
});

Route::get('/', function () {
    return view('welcome');
});
