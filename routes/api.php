<?php

use App\Actions\MailResend;
use App\Actions\CommentFind;
use App\Actions\CommentList;
use App\Models\DocumentType;
use Illuminate\Http\Request;
use App\Actions\NotifyPeople;
use App\Actions\CommentCreate;
use App\Actions\CommentDelete;
use App\Actions\CommentUpdate;
use App\Actions\CommentResolve;
use App\Actions\FeedbackSubmit;
use App\Actions\CommentTypeList;
use App\Actions\CommentUnresolve;
use App\Actions\LogEntrySearch;
use App\Actions\NotificationMarkRead;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CdwgController;
use App\Http\Controllers\Api\RolesController;
use App\Http\Controllers\Api\MailLogController;
use App\Http\Controllers\Api\MailDraftController;
use App\Http\Controllers\Api\GeneLookupController;
use App\Http\Controllers\Api\SystemInfoController;
use App\Http\Controllers\Api\AnnualUpdateController;
use App\Http\Controllers\Api\DiseaseLookupController;
use App\Http\Controllers\Api\MoiLookupController;
use App\Http\Controllers\Api\DocumentationController;
use App\Http\Controllers\ImpersonateSearchController;
use App\Modules\User\Http\Controllers\CurrentUserController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['middleware' => ['guest']], function () {
    Route::post('/send-reset-password-link', [AuthController::class, 'sendResetPasswordLink']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

Route::get('/document-types', function () {
    return DocumentType::all();
});

Route::get('/authenticated', [AuthController::class, 'isAuthenticated']);

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get('/system-info', [SystemInfoController::class, 'index']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::get('/current-user', [CurrentUserController::class, 'show']);

    Route::post('/feedback', FeedbackSubmit::class);

    Route::get('/email-drafts/groups/{group:uuid}', [MailDraftController::class, 'makeDraft']);
    // Route::get('/email-drafts/{applicationUuid}/{approvedStepNumber}', [MailDraftController::class, 'show']);


    Route::get('/mail-log', [MailLogController::class, 'index']);
    Route::post('/mail', MailResend::class);

    Route::post('/announcements', NotifyPeople::class);

    Route::get('/impersonate/search', [ImpersonateSearchController::class, 'index']);

    Route::put('/notifications/{notificationId}', NotificationMarkRead::class);

    Route::group(['prefix' => '/annual-updates'], function () {
        Route::get('', [AnnualUpdateController::class, 'index']);
        Route::get('/windows', [AnnualUpdateController::class, 'windows']);
        Route::post('/export', [AnnualUpdateController::class, 'export']);
        Route::get('/{id}', [AnnualUpdateController::class, 'show']);
    });

    Route::get('/roles', [RolesController::class, 'index']);

    Route::group(['prefix' => '/comments'], function () {
        Route::post('/', CommentCreate::class);
        Route::get('/', CommentList::class);
        Route::get('/{comment:id}', CommentFind::class);
        Route::put('/{comment:id}', CommentUpdate::class);
        Route::delete('/{comment:id}', CommentDelete::class);
        Route::post('/{comment:id}/resolved', CommentResolve::class);
        Route::post('/{comment:id}/unresolved', CommentUnresolve::class);
    });
    Route::get('/comment-types', CommentTypeList::class);

    Route::get('/activity-logs', LogEntrySearch::class);
});

Route::get('/cdwgs', [CdwgController::class, 'index']);

Route::post('/genes/check-genes', [GeneLookupController::class, 'check']);
Route::get('/diseases/search', [DiseaseLookupController::class, 'search']);
Route::get('/diseases/{mondo_id}', [DiseaseLookupController::class, 'show']);

Route::get('/genes/search', [GeneLookupController::class, 'search']);
Route::get('/genes/{hgnc_id}', [GeneLookupController::class, 'show']);

Route::get('/curations', [GeneLookupController::class, 'curations']);
Route::post('/curationids', [GeneLookupController::class, 'curationids']);
Route::get('/mois', [MoiLookupController::class, 'index']);

Route::get('/docs', [DocumentationController::class, 'index']);
Route::get('/docs/{slug}', [DocumentationController::class, 'show']);
