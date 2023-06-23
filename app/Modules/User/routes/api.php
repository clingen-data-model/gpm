<?php

use App\Modules\User\Actions\UserRolesAndPermissionsUpdate;
use App\Modules\User\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/users')->middleware('api')->group(function () {
    Route::get('/', [UserController::class, 'index']);
    Route::get('/{user}', [UserController::class, 'show']);
    Route::put('/{user}/roles-and-permissions', UserRolesAndPermissionsUpdate::class);
});
