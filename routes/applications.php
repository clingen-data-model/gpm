<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ApplicationController;
use App\Http\Controllers\Api\ApplicationLogController;
use App\Http\Controllers\Api\ApplicationStepController;
use App\Http\Controllers\Api\ApplicationContactController;
use App\Http\Controllers\Api\ApplicationDocumentController;
use App\Http\Controllers\Api\ApplicationNextActionsController;

Route::get('/applications', [ApplicationController::class, 'index']);

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::post('/applications', [ApplicationController::class, 'store']);
    Route::get('/applications/{app_uuid}', [ApplicationController::class, 'show']);
    Route::put('/applications/{app_uuid}', [ApplicationController::class, 'update']);
    Route::delete('/applications/{app_uuid}', [ApplicationController::class, 'destroy']);
    
    Route::get('/applications/{app_uuid}/contacts', [ApplicationContactController::class, 'index']);
    Route::post('/applications/{app_uuid}/contacts', [ApplicationContactController::class, 'store']);
    // Route::put('/applications/{app_uuid}/contacts', [ApplicationContactController::class, 'update']);
    Route::delete('/applications/{app_uuid}/contacts/{person_uuid}', [ApplicationContactController::class, 'remove']);
    
    Route::post('/applications/{app_uuid}/current-step/approve', [ApplicationStepController::class, 'approve']);
    Route::put('/applications/{app_uuid}/approve', [ApplicationStepController::class, 'updateApprovalDate']);
    
    Route::post('/applications/{app_uuid}/documents', [ApplicationDocumentController::class, 'store']);
    Route::put('/applications/{app_uuid}/documents/{doc_uuid}', [ApplicationDocumentController::class, 'update']);
    Route::delete('/applications/{app_uuid}/documents/{doc_uuid}', [ApplicationDocumentController::class, 'destroy']);
    Route::post('/applications/{app_uuid}/documents/{doc_uuid}/final', [ApplicationDocumentController::class, 'markFinal']);
    
    Route::get('/applications/{app_uuid}/log-entries', [ApplicationLogController::class, 'index']);
    Route::post('/applications/{app_uuid}/log-entries', [ApplicationLogController::class, 'store']);
    Route::put('/applications/{app_uuid}/log-entries/{id}', [ApplicationLogController::class, 'update']);
    Route::delete('/applications/{app_uuid}/log-entries/{id}', [ApplicationLogController::class, 'destroy']);
    
    Route::post('/applications/{app_uuid}/next-actions', [ApplicationNextActionsController::class, 'store']);
    Route::put('/applications/{app_uuid}/next-actions/{id}', [ApplicationNextActionsController::class, 'update']);
    Route::delete('/applications/{app_uuid}/next-actions/{id}', [ApplicationNextActionsController::class, 'destroy']);
    Route::post('/applications/{app_uuid}/next-actions/{action_uuid}/complete', [ApplicationNextActionsController::class, 'complete']);
});
