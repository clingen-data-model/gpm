<?php

use Illuminate\Support\Facades\Route;
use App\Tasks\Http\Controllers\Api\TaskController;

Route::group([
    'prefix' => 'api/tasks',
    'middleware' => ['api', 'auth:sanctum']
], function () {
    Route::get('/', [TaskController::class, 'index']);
});
