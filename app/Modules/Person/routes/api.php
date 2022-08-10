<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Modules\Person\Models\Institution;
use App\Modules\Person\Actions\InviteReset;
use App\Modules\Person\Actions\PersonMerge;
use App\Modules\Person\Actions\InviteRedeem;
use App\Modules\Person\Actions\PersonDelete;
use App\Modules\Person\Actions\ProfileUpdate;
use App\Modules\Person\Actions\InstitutionCreate;
use App\Modules\Person\Actions\InstitutionsMerge;
use App\Modules\Person\Actions\InstitutionUpdate;
use App\Modules\Person\Actions\InviteValidateCode;
use App\Http\Controllers\Api\InstitutionController;
use App\Modules\Person\Actions\MarkNotificationRead;
use App\Modules\Person\Actions\InstitutionMarkApproved;
use App\Modules\Person\Http\Controllers\Api\ApiController;
use App\Modules\Person\Actions\InviteRedeemForExistingUser;
use App\Modules\Person\Actions\PersonPhotoStore;
use App\Modules\Person\Http\Controllers\Api\InviteController;
use App\Modules\Person\Http\Controllers\Api\PeopleController;
use App\Modules\Person\Http\Controllers\Api\TimezoneController;
use App\Modules\Person\Http\Controllers\Api\PersonEmailController;
use App\Modules\Person\Http\Controllers\Api\ActivityLogsController;
use App\Modules\Person\Http\Controllers\Api\PersonNotificationController;
use App\Modules\Person\Models\Person;

Route::group([
    'prefix' => 'api/people',
    'middleware' => ['api']
], function () {
    Route::get('/institutions', function (Request $request) {
        return Institution::select('name', 'abbreviation', 'id', 'url')->get();
    });

    Route::get('/timezones', function (Request $request) {
        return Institution::select('name', 'abbreviation', 'id')->get();
    });

    Route::group([
        'middleware' => ['auth:sanctum']
    ], function () {
        Route::get('/', [PeopleController::class, 'index']);
        Route::get('/invites/', [InviteController::class, 'index']);

        Route::put('/merge', PersonMerge::class);

        Route::get('/{person:uuid}', [PeopleController::class, 'show']);
        Route::delete('/{person:uuid}', PersonDelete::class);
        Route::put('/{person:uuid}/profile', ProfileUpdate::class);
        // No post route b/c person creation currently happens when adding members to groups.

        Route::get('/{person:uuid}/activity-logs', [ActivityLogsController::class, 'index']);
        Route::get('/{person:uuid}/email', [PersonEmailController::class, 'index']);
        Route::get('/{person:uuid}/notifications/unread', [PersonNotificationController::class, 'unread']);

        Route::post('/{person:uuid}/profile-photo', PersonPhotoStore::class);
        Route::get('/{person:uuid}/profile-photo', function (Person $person) {
            if (!\Storage::disk('profile-photos')->exists($person->profile_photo)) {
                return response()->file(public_path('images/default_profile.jpg'));
            }

            return \Storage::disk('profile-photos')->get($person->profile_photo);
        });
    });

    Route::get('/invites/{code}', InviteValidateCode::class);
    Route::put('/invites/{code}', InviteRedeem::class);
    Route::put('/existing-user/invites/{code}', InviteRedeemForExistingUser::class);
    Route::put('/invites/{code}/reset', InviteReset::class);

    // Lookups
    Route::get('/lookups/timezones', [TimezoneController::class, 'index'])
        ->name('people.timezones.index');

    Route::get('/lookups/{model}', [ApiController::class, 'index'])
    ->name('people.catchall.index');

    Route::get('/lookups/{model}/{id}', [ApiController::class, 'show'])
        ->name('people.catchall.show');

});


Route::group([
    'prefix' => 'api/institutions',
    'middleware' => ['api']
], function () {
    Route::group(['middleware' => 'auth:sanctum'], function () {
        Route::get('/', [InstitutionController::class, 'index']);
        Route::post('/', InstitutionCreate::class);
        Route::put('/merge', InstitutionsMerge::class);
        Route::put('/{institution}', InstitutionUpdate::class);
        Route::put('/{institution}/approved', InstitutionMarkApproved::class);
    });
});
