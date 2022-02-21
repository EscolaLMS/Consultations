<?php

use EscolaLms\Consultations\Http\Controllers\ConsultationAPIController;
use EscolaLms\Consultations\Http\Controllers\ConsultationController;
use Illuminate\Support\Facades\Route;

// admin endpoints
Route::group(['middleware' => ['auth:api'], 'prefix' => 'api/admin'], function () {
    Route::resource('consultations', ConsultationController::class);
});

// user endpoints
Route::group(['middleware' => ['auth:api'], 'prefix' => 'api/consultations'], function () {
    Route::get('/', [ConsultationAPIController::class, 'index']);
    Route::get('/{id}', [ConsultationController::class, 'show']);
    Route::post('/report-term/{orderItemId}', [ConsultationAPIController::class, 'reportTerm']);
    Route::get('/proposed-terms/{orderItemId}', [ConsultationAPIController::class, 'proposedTerms']);
    Route::get('/approve-term/{consultationTermId}', [ConsultationAPIController::class, 'approveTerm']);
    Route::get('/reject-term/{consultationTermId}', [ConsultationAPIController::class, 'rejectTerm']);
    Route::get('/generate-jitsi/{consultationTermId}', [ConsultationAPIController::class, 'generateJitsi']);
});

