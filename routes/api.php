<?php

use App\Models\DocumentType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CdwgController;
use App\Http\Controllers\Api\PeopleController;
use App\Http\Controllers\Api\MailLogController;

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

Route::get('/document-types', function () {
    return DocumentType::all();
});

Route::get('/authenticated', [AuthController::class, 'isAuthenticated']);

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::get('/current-user', function (Request $request) {
        return $request->user();
    });    

    Route::get('/mail-log', [MailLogController::class, 'index']);
});
Route::get('/cdwgs', [CdwgController::class, 'index']);