<?php

use App\Actions\ReportPeopleMake;
use App\Actions\ReportSummaryMake;
use App\Actions\ReportCountriesMake;
use App\Actions\ReportGcepGenesMake;
use App\Actions\ReportVcepGenesMake;
use Illuminate\Support\Facades\Route;
use App\Actions\ReportMultipleEpsMake;
use App\Actions\ReportInstitutionsMake;
use App\Http\Controllers\ViewController;
use App\Actions\ReportVcepApplicationMake;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\SocialLoginController;
use App\Modules\Group\Actions\GroupMembersMakeCsv;
use App\Modules\ExpertPanel\Actions\CoiReportMakePdf;
use App\Modules\Group\Actions\SubgroupMembersMakeExcel;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/{any}', [ViewController::class, 'app'])
    ->where('any', '^(?!(api|sanctum|impersonate|dev|documents|downloads|clockwork|profile-photos|storage|auth)).*$');

Route::get('/documents/{uuid?}', [DocumentController::class, 'show'])->middleware('auth:sanctum');
Route::get('/storage/profile-photos/{filename}', function ($filename) {
    return redirect('/profile-photos/'.$filename, 301);
});

Route::group(['prefix' => '/api/report'], function () {
    Route::get('/basic-summary', ReportSummaryMake::class);
    Route::get('/vcep-application-summary', ReportVcepApplicationMake::class);
    Route::get('/gcep-genes', ReportGcepGenesMake::class);
    Route::get('/vcep-genes', ReportVcepGenesMake::class);
    Route::get('/institutions', ReportInstitutionsMake::class);
    Route::get('/countries', ReportCountriesMake::class);
    Route::get('/people', ReportPeopleMake::class);
    Route::get('/people-in-multiple-eps', ReportMultipleEpsMake::class);

    Route::group(['prefix' => '/groups/{group:uuid}'], function () {
        Route::get('/coi-report', CoiReportMakePdf::class);
        Route::get('/member-export', GroupMembersMakeCsv::class);
        Route::get('/subgroup-member-export', SubgroupMembersMakeExcel::class);
    });
});

Route::group(['prefix' => 'auth/{provider}'], function() {
    Route::get('/redirect', [SocialLoginController::class, 'handleRedirect']);
    Route::get('/callback', [SocialLoginController::class, 'handleCallback']);
});

Route::impersonate();
