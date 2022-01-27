<?php

use App\Modules\User\Actions\RolesAndPermissionsUpdate;
use Illuminate\Support\Facades\Route;
use App\Modules\User\Http\Controllers\Api\UserController;

Route::group([
    'prefix' => 'api/users',
    'middleware' => ['api']
], function () {
    Route::get('/', [UserController::class, 'index']);
    Route::get('/{user}', [UserController::class, 'show']);
    Route::put('/{user}/roles-and-permissions', RolesAndPermissionsUpdate::class);
});
