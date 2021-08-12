<?php

use Illuminate\Support\Facades\Route;
use App\Modules\ExpertPanel\Actions\AddApplicationDocument;
use App\Modules\ExpertPanel\Actions\DeleteApplicationDocument;
use App\Modules\ExpertPanel\Actions\MarkDocumentFinal;
use App\Modules\ExpertPanel\Actions\UpdateApplicationDocument;
use App\Modules\ExpertPanel\Http\Controllers\Api\SimpleCoiController;
use App\Modules\ExpertPanel\Http\Controllers\Api\ApplicationController;
use App\Modules\ExpertPanel\Http\Controllers\Api\ApplicationLogController;
use App\Modules\ExpertPanel\Http\Controllers\Api\ApplicationStepController;
use App\Modules\ExpertPanel\Http\Controllers\Api\ApplicationContactController;
use App\Modules\ExpertPanel\Http\Controllers\Api\NextActionAssigneeController;
use App\Modules\ExpertPanel\Http\Controllers\Api\ApplicationDocumentController;
use App\Modules\ExpertPanel\Http\Controllers\Api\ApplicationNextActionsController;

Route::get('/next-actions/assignees', [NextActionAssigneeController::class, 'index']);

Route::group(['prefix' => 'api/coi'], function () {
    Route::get('/{code}/application', [SimpleCoiController::class, 'getApplication']);
    Route::post('/{code}', [SimpleCoiController::class, 'store']);    
});

Route::group([
    'prefix' => 'api/applications',
    'middleware' => ['api']
], function () {

    Route::get('/', [ApplicationController::class, 'index']);

    Route::group(['middleware' => ['auth:sanctum']], function () {
        Route::post('/', [ApplicationController::class, 'store']);
        Route::get('/{app_uuid}', [ApplicationController::class, 'show']);
        Route::put('/{app_uuid}', [ApplicationController::class, 'update']);
        Route::delete('/{app_uuid}', [ApplicationController::class, 'destroy']);
        
        Route::post('/{app_uuid}/contacts', [ApplicationContactController::class, 'store']);
        Route::get('/{app_uuid}/contacts', [ApplicationContactController::class, 'index']);
        Route::delete('/{app_uuid}/contacts/{person_uuid}', [ApplicationContactController::class, 'remove']);
        
        Route::post('/{app_uuid}/current-step/approve', [ApplicationStepController::class, 'approve']);
        Route::put('/{app_uuid}/approve', [ApplicationStepController::class, 'updateApprovalDate']);
        
        // Route::post('/{app_uuid}/documents', [ApplicationDocumentController::class, 'store']);
        // Route::put('/{app_uuid}/documents/{doc_uuid}', [ApplicationDocumentController::class, 'update']);
        // Route::delete('/{app_uuid}/documents/{doc_uuid}', [ApplicationDocumentController::class, 'destroy']);
        // Route::post('/{app_uuid}/documents/{doc_uuid}/final', [ApplicationDocumentController::class, 'markFinal']);
        
        Route::post('/{app_uuid}/documents', AddApplicationDocument::class);
        Route::put('/{app_uuid}/documents/{doc_uuid}', UpdateApplicationDocument::class);
        Route::post('/{app_uuid}/documents/{doc_uuid}/final', MarkDocumentFinal::class);
        Route::delete('/{app_uuid}/documents/{doc_uuid}', DeleteApplicationDocument::class);
        
        Route::get('/{app_uuid}/log-entries', [ApplicationLogController::class, 'index']);
        Route::post('/{app_uuid}/log-entries', [ApplicationLogController::class, 'store']);
        Route::put('/{app_uuid}/log-entries/{id}', [ApplicationLogController::class, 'update']);
        Route::delete('/{app_uuid}/log-entries/{id}', [ApplicationLogController::class, 'destroy']);
        
        Route::post('/{app_uuid}/next-actions', [ApplicationNextActionsController::class, 'store']);
        Route::put('/{app_uuid}/next-actions/{id}', [ApplicationNextActionsController::class, 'update']);
        Route::delete('/{app_uuid}/next-actions/{id}', [ApplicationNextActionsController::class, 'destroy']);
        Route::post(
            '/{app_uuid}/next-actions/{action_uuid}/complete', 
            [ApplicationNextActionsController::class, 'complete']
        );
    });
    
});