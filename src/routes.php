<?php

use EscolaLms\Consultations\Http\Controllers\ConsultationController;
use Illuminate\Support\Facades\Route;

// admin endpoints
Route::group(['middleware' => ['auth:api'], 'prefix' => 'api/admin'], function () {
    Route::resource('consultations', ConsultationController::class);
});

// user endpoints
Route::group(['middleware' => ['auth:api'], 'prefix' => 'api'], function () {
});

// public routes
Route::group(['prefix' => 'api'], function () {
});
