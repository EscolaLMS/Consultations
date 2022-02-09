<?php

use EscolaLms\Consultations\Http\Controllers\ConsultationAPIController;
use EscolaLms\Consultations\Http\Controllers\ConsultationController;
use EscolaLms\Consultations\Http\Controllers\OrderApiController;
use Illuminate\Support\Facades\Route;

// admin endpoints
Route::group(['middleware' => ['auth:api'], 'prefix' => 'api/admin'], function () {
    Route::resource('consultations', ConsultationController::class);
});

// user endpoints
Route::group(['middleware' => ['auth:api'], 'prefix' => 'api/consultations'], function () {
    Route::get('/', [ConsultationController::class, 'index']);
    Route::get('/{id}', [ConsultationController::class, 'show']);
});

Route::group(['prefix' => 'api/orders', 'middleware' => ['auth:api']], function () {
    Route::get('report-term/{id}', [OrderApiController::class, 'reportTerm']);
});

// public routes
Route::group(['prefix' => 'api'], function () {
});
