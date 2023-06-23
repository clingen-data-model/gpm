<?php

use App\Tasks\Http\Controllers\Api\TaskController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/tasks')->middleware('api', 'auth:sanctum')->group(function () {
    Route::get('/', [TaskController::class, 'index']);
});
