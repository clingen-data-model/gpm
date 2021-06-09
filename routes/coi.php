<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SimpleCoiController;

Route::group([], function () {
    Route::get('coi/{code}/application', [SimpleCoiController::class, 'getApplication']);
    Route::post('coi/{code}', [SimpleCoiController::class, 'store']);    
});