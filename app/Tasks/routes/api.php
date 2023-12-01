<?php

use App\Tasks\Http\Controllers\Api\TaskController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'api/tasks',
    'middleware' => ['api', 'auth:sanctum'],
], function () {
    Route::get('/', [TaskController::class, 'index']);
});
