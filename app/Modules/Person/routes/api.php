<?php

use App\Modules\Person\Actions\ProfileUpdate;
use Illuminate\Support\Facades\Route;
use App\Modules\Person\Http\Controllers\Api\PeopleController;

Route::group([
    'prefix' => 'api/people',
    'middleware' => ['api','auth:sanctum']
], function () {
    Route::get('/', [PeopleController::class, 'index']);
    Route::post('/', [PeopleController::class, 'store']);
    Route::get('/{uuid}', [PeopleController::class, 'show']);
    Route::put('/{uuid}', [PeopleController::class, 'update']);

    Route::put('/{uuid}/profile', ProfileUpdate::class);
});
