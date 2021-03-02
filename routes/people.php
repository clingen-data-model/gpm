<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PeopleController;

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get('/people', [PeopleController::class, 'index']);
    Route::post('/people', [PeopleController::class, 'store']);
    Route::get('/people/{uuid}', [PeopleController::class, 'show']);
});
