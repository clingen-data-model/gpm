<?php

use App\Actions\ReportSummaryMake;
use App\Actions\ReportVcepApplicationReport;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ViewController;
use App\Http\Controllers\DocumentController;
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
    ->where('any', '^(?!(api|sanctum|impersonate|dev|documents|downloads|clockwork|profile-photos)).*$');

Route::get('/documents/{uuid?}', [DocumentController::class, 'show'])->middleware('auth:sanctum');

Route::get('/api/report/basic-summary', ReportSummaryMake::class);
Route::get('/api/report/vcep-application-summary', ReportVcepApplicationReport::class);
Route::get('/api/report/groups/{group:uuid}/coi-report', CoiReportMakePdf::class);
Route::get('/api/report/groups/{group:uuid}/member-export', GroupMembersMakeCsv::class);
Route::get('/api/report/groups/{group:uuid}/subgroup-member-export', SubgroupMembersMakeExcel::class);

Route::impersonate();
