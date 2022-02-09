<?php

use EscolaLms\Consultations\Http\Controllers\ConsultationAPIController;
use EscolaLms\Consultations\Http\Controllers\ConsultationController;
use Illuminate\Support\Facades\Route;

// admin endpoints
Route::group(['middleware' => ['auth:api'], 'prefix' => 'api/admin'], function () {
    Route::resource('consultations', ConsultationController::class);
});

// user endpoints
Route::group(['middleware' => ['auth:api'], 'prefix' => 'api'], function () {
    Route::get('consultations/{id}', ConsultationController::class);
    Route::get('consultations', ConsultationController::class);
    Route::post('consultations/report-term/{id}', [ConsultationAPIController::class, 'reportTerm']);
});

// public routes
Route::group(['prefix' => 'api'], function () {
});
