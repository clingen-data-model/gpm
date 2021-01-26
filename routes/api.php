<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ApplicationController;
use App\Http\Controllers\Api\ApplicationStepController;
use App\Http\Controllers\Api\ApplicationContactController;
use App\Http\Controllers\Api\ApplicationDocumentController;

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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/applications', [ApplicationController::class, 'store']);

Route::post('/applications/{uuid}/contacts', [ApplicationContactController::class, 'store']);
Route::get('/applications/{uuid}/contacts', [ApplicationContactController::class, 'index']);

Route::post('/applications/{uuid}/current-step/approve', [ApplicationStepController::class, 'approve']);

Route::post('/applications/{uuid}/documents', [ApplicationDocumentController::class, 'store']);
Route::post('/applications/{app_uuid}/documents/{doc_uuid}/review', [ApplicationDocumentController::class, 'markReviewed']);
