<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dev\FakeIdpController;

Route::group(['middleware' => ['auth']], function () {
    Route::get('logs', '\Rap2hpoutre\LaravelLogViewer\LogViewerController@index');
});

// Fake identity provider (IDP_DRIVER=fake, never in production): lets the SPA
// sign in without any network access. See app/Services/Idp/Fake.
Route::group(['prefix' => 'idp', 'middleware' => ['idp.fake']], function () {
    Route::get('users', [FakeIdpController::class, 'users'])->name('dev.idp.users');
    Route::post('token', [FakeIdpController::class, 'token'])->name('dev.idp.token');
});
