<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Person\Actions\InviteRedeem;
use App\Modules\Person\Actions\ProfileUpdate;
use App\Modules\Person\Actions\InviteValidateCode;
use App\Modules\Person\Http\Controllers\Api\InviteController;
use App\Modules\Person\Http\Controllers\Api\PeopleController;

Route::group([
    'prefix' => 'api/people',
    'middleware' => ['api']
], function () {
    Route::group([
        'middleware' => ['auth:sanctum']
    ], function () {
        Route::get('/', [PeopleController::class, 'index']);
        Route::post('/', [PeopleController::class, 'store']);
        Route::get('/invites/', [InviteController::class, 'index']);
        Route::get('/{uuid}', [PeopleController::class, 'show']);
        Route::put('/{uuid}', [PeopleController::class, 'update']);
        
        Route::put('/{uuid}/profile', ProfileUpdate::class);
    });

    Route::get('/invites/{code}', InviteValidateCode::class);
    Route::put('/invites/{code}', InviteRedeem::class);
});
