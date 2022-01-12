<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Modules\Person\Models\Institution;
use App\Modules\Person\Actions\InviteReset;
use App\Modules\Person\Actions\InviteRedeem;
use App\Modules\Person\Actions\ProfileUpdate;
use App\Modules\Person\Actions\InviteValidateCode;
use App\Modules\Person\Actions\MarkNotificationRead;
use App\Modules\Person\Http\Controllers\Api\ApiController;
use App\Modules\Person\Actions\InviteRedeemForExistingUser;
use App\Modules\Person\Http\Controllers\Api\InviteController;
use App\Modules\Person\Http\Controllers\Api\PeopleController;
use App\Modules\Person\Http\Controllers\Api\TimezoneController;
use App\Modules\Person\Http\Controllers\Api\PersonEmailController;
use App\Modules\Person\Http\Controllers\Api\PersonNotificationController;

Route::group([
    'prefix' => 'api/people',
    'middleware' => ['api']
], function () {
    Route::get('/institutions', function (Request $request) {
        return Institution::select('name', 'abbreviation', 'id')->get();
    });

    Route::get('/timezones', function (Request $request) {
        return Institution::select('name', 'abbreviation', 'id')->get();
    });

    Route::group([
        'middleware' => ['auth:sanctum']
    ], function () {
        Route::get('/', [PeopleController::class, 'index']);
        Route::post('/', [PeopleController::class, 'store']);
        Route::get('/invites/', [InviteController::class, 'index']);
        Route::get('/{uuid}', [PeopleController::class, 'show']);
        Route::put('/{uuid}', [PeopleController::class, 'update']);
        
        Route::put('/{uuid}/profile', ProfileUpdate::class);
        Route::get('/{person:uuid}/email', [PersonEmailController::class, 'index']);
        Route::get('/{person:uuid}/notifications/unread', [PersonNotificationController::class, 'unread']);
    });

    Route::get('/invites/{code}', InviteValidateCode::class);
    Route::put('/invites/{code}', InviteRedeem::class);
    Route::put('/existing-user/invites/{code}', InviteRedeemForExistingUser::class);
    Route::put('/invites/{code}/reset', InviteReset::class);
    
    Route::get('/lookups/timezones', [TimezoneController::class, 'index'])
        ->name('people.timezones.index');

    // index
    Route::get('/lookups/{model}', [ApiController::class, 'index'])
    ->name('people.catchall.index');
    
    // show
    Route::get('/lookups/{model}/{id}', [ApiController::class, 'show'])
        ->name('people.catchall.show');
});
