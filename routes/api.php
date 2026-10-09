<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CityController;
use App\Http\Controllers\Api\CityVisitController;
use App\Http\Controllers\Api\LeadController;
use Illuminate\Support\Facades\Route;

Route::get('/cities', [CityController::class, 'index']);

Route::middleware('throttle:10,1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth:api')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/cities/{city}/visits', [CityVisitController::class, 'index']);
    Route::get('/leads', [LeadController::class, 'index']);
    Route::post('/leads', [LeadController::class, 'store']);
    Route::delete('/leads/{lead}', [LeadController::class, 'destroy'])->whereNumber('lead');
});
