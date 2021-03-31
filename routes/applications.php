<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ApplicationController;
use App\Http\Controllers\Api\ApplicationLogController;
use App\Http\Controllers\Api\ApplicationStepController;
use App\Http\Controllers\Api\ApplicationContactController;
use App\Http\Controllers\Api\ApplicationDocumentController;
use App\Http\Controllers\Api\ApplicationNextActionsController;

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get('/applications', [ApplicationController::class, 'index']);
    Route::post('/applications', [ApplicationController::class, 'store']);
    Route::get('/applications/{app_uuid}', [ApplicationController::class, 'show']);
    Route::put('/applications/{app_uuid}', [ApplicationController::class, 'update']);
    
    Route::get('/applications/{app_uuid}/contacts', [ApplicationContactController::class, 'index']);
    Route::post('/applications/{app_uuid}/contacts', [ApplicationContactController::class, 'store']);
    // Route::put('/applications/{app_uuid}/contacts', [ApplicationContactController::class, 'update']);
    Route::delete('/applications/{app_uuid}/contacts/{person_uuid}', [ApplicationContactController::class, 'remove']);
    
    Route::post('/applications/{app_uuid}/current-step/approve', [ApplicationStepController::class, 'approve']);
    
    Route::post('/applications/{app_uuid}/documents', [ApplicationDocumentController::class, 'store']);
    Route::post('/applications/{app_uuid}/documents/{doc_uuid}/review', [ApplicationDocumentController::class, 'markReviewed']);
    Route::post('/applications/{app_uuid}/documents/{doc_uuid}/final', [ApplicationDocumentController::class, 'markFinal']);
    
    Route::get('/applications/{app_uuid}/log-entries', [ApplicationLogController::class, 'index']);
    Route::post('/applications/{app_uuid}/log-entries', [ApplicationLogController::class, 'store']);
    
    Route::post('/applications/{app_uuid}/next-actions', [ApplicationNextActionsController::class, 'store']);
    Route::post('/applications/{app_uuid}/next-actions/{action_uuid}/complete', [ApplicationNextActionsController::class, 'complete']);
});