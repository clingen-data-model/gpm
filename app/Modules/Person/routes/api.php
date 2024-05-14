<?php

use Illuminate\Http\Request;
use App\Modules\Person\Models\Person;
use Illuminate\Support\Facades\Route;
use App\Modules\Person\Models\Institution;
use App\Modules\Person\Actions\InviteReset;
use App\Modules\Person\Actions\PersonMerge;
use App\Modules\Person\Actions\InviteRedeem;
use App\Modules\Person\Actions\PersonDelete;
use App\Modules\Person\Actions\ProfileUpdate;
use App\Modules\Person\Actions\DemographicsUpdate;
use App\Modules\Person\Actions\ExpertiseCreate;
use App\Modules\Person\Actions\ExpertiseDelete;
use App\Modules\Person\Actions\ExpertiseSearch;
use App\Modules\Person\Actions\ExpertisesMerge;
use App\Modules\Person\Actions\ExpertiseUpdate;
use App\Modules\Person\Actions\CredentialCreate;
use App\Modules\Person\Actions\CredentialDelete;
use App\Modules\Person\Actions\CredentialSearch;
use App\Modules\Person\Actions\CredentialsMerge;
use App\Modules\Person\Actions\CredentialUpdate;
use App\Modules\Person\Actions\PersonPhotoStore;
use App\Modules\Person\Actions\InstitutionCreate;
use App\Modules\Person\Actions\InstitutionDelete;
use App\Modules\Person\Actions\InstitutionsMerge;
use App\Modules\Person\Actions\InstitutionUpdate;
use App\Modules\Person\Actions\InviteValidateCode;
use App\Http\Controllers\Api\InstitutionController;
use App\Http\Controllers\CountryController;
use App\Modules\Person\Actions\MarkNotificationRead;
use App\Modules\Person\Actions\InstitutionMarkApproved;
use App\Modules\Person\Http\Controllers\Api\ApiController;
use App\Modules\Person\Actions\InviteRedeemForExistingUser;
use App\Modules\Person\Http\Controllers\Api\InviteController;
use App\Modules\Person\Http\Controllers\Api\PeopleController;
use App\Modules\Person\Http\Controllers\Api\TimezoneController;
use App\Modules\Person\Http\Controllers\Api\PersonEmailController;
use App\Modules\Person\Http\Controllers\Api\ActivityLogsController;
use App\Modules\Person\Http\Controllers\Api\PersonNotificationController;

Route::group([
    'prefix' => 'api/people',
    'middleware' => ['api']
], function () {
    Route::get('/institutions', [InstitutionController::class, 'index']);

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
        Route::put('/{person:uuid}/demographics', DemographicsUpdate::class);
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
    'middleware' => ['api', 'auth:sanctum']
], function () {
    Route::get('/', [InstitutionController::class, 'index']);
    Route::post('/', InstitutionCreate::class);
    Route::put('/merge', InstitutionsMerge::class);
    Route::put('/{institution}', InstitutionUpdate::class);
    Route::delete('/{institution}', InstitutionDelete::class);
    Route::put('/{institution}/approved', InstitutionMarkApproved::class);
});

Route::group([
    'prefix' => 'api/credentials',
    'middleware' => ['api', 'auth:sanctum']
], function () {
    Route::post('/', CredentialCreate::class);
    Route::get('/', CredentialSearch::class);
    Route::put('/merge', CredentialsMerge::class);
    Route::put('/{credential}', CredentialUpdate::class);
    Route::delete('/{credential}', CredentialDelete::class);
});

Route::group([
    'prefix' => 'api/expertises',
    'middleware' => ['api', 'auth:sanctum']
], function () {
    Route::post('/', ExpertiseCreate::class);
    Route::get('/', ExpertiseSearch::class);
    Route::put('/merge', ExpertisesMerge::class);
    Route::put('/{expertise}', ExpertiseUpdate::class);
    Route::delete('/{expertise}', ExpertiseDelete::class);
});

Route::group([
    'prefix' => 'api/countries',
    // 'middleware' => ['api', 'auth:sanctum']
], function () {
    Route::get('/', [CountryController::class, 'index']);
});
