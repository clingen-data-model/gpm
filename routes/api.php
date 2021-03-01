<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CdwgController;
use App\Http\Controllers\Api\PeopleController;
use App\Http\Controllers\Api\ApplicationController;
use App\Http\Controllers\Api\ApplicationLogController;
use App\Http\Controllers\Api\ApplicationStepController;
use App\Http\Controllers\Api\ApplicationContactController;
use App\Http\Controllers\Api\ApplicationDocumentController;
use App\Http\Controllers\Api\ApplicationNextActionsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['middleware' => ['guest']], function () {
    Route::post('/send-reset-password-link', [AuthController::class, 'sendResetPasswordLink']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::get('/current-user', function (Request $request) {
        return $request->user();
    });

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
    
    Route::get('/applications/{app_uuid}/log-entries', [ApplicationLogController::class, 'index']);
    Route::post('/applications/{app_uuid}/log-entries', [ApplicationLogController::class, 'store']);
    
    Route::post('/applications/{app_uuid}/next-actions', [ApplicationNextActionsController::class, 'store']);
    Route::post('/applications/{app_uuid}/next-actions/{action_uuid}/complete', [ApplicationNextActionsController::class, 'complete']);
    
    Route::get('/people', [PeopleController::class, 'index']);
    Route::post('/people', [PeopleController::class, 'store']);
    
});
Route::get('/cdwgs', [CdwgController::class, 'index']);