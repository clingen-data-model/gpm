<?php

use Illuminate\Support\Facades\Route;
use App\Modules\ExpertPanel\Http\Controllers\Api\SimpleCoiController;

Route::get('/report/{coiCode}', [SimpleCoiController::class, 'getReport'])->middleware('auth:sanctum');
