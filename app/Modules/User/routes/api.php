<?php

use App\Modules\User\Actions\ImpersonateStart;
use App\Modules\User\Actions\ImpersonateStop;
use App\Modules\User\Actions\UserRolesAndPermissionsUpdate;
use Illuminate\Support\Facades\Route;
use App\Modules\User\Http\Controllers\Api\UserController;

Route::group([
    'prefix' => 'api/users',
    'middleware' => ['api']
], function () {
    Route::get('/', [UserController::class, 'index']);
    Route::get('/{user}', [UserController::class, 'show']);
});

Route::group([
    'prefix' => 'api/users',
    'middleware' => ['api', 'auth.clerk']
], function () {
    Route::put('/{user}/roles-and-permissions', UserRolesAndPermissionsUpdate::class);
});

Route::group([
    'prefix' => 'api/impersonate',
    'middleware' => ['api', 'auth.clerk', 'auth'],
], function () {
    Route::post('/{user}', ImpersonateStart::class);
    Route::delete('/', ImpersonateStop::class);
});
