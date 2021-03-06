<?php

use App\Models\AnnualUpdate;
use Illuminate\Support\Facades\Route;
use App\Modules\Group\Actions\GenesAdd;
use App\Modules\Group\Actions\MemberAdd;
use App\Modules\Group\Actions\GeneRemove;
use App\Modules\Group\Actions\GeneUpdate;
use App\Modules\Group\Actions\DocumentAdd;
use App\Modules\Group\Actions\GroupCreate;
use App\Modules\Group\Actions\GroupDelete;
use App\Modules\Group\Actions\MemberInvite;
use App\Modules\Group\Actions\MemberRemove;
use App\Modules\Group\Actions\MemberRetire;
use App\Modules\Group\Actions\MemberUpdate;
use App\Modules\Group\Actions\ParentUpdate;
use App\Modules\Group\Actions\DocumentUpdate;
use App\Modules\Group\Actions\MemberUnretire;
use App\Modules\Group\Actions\GroupNameUpdate;
use App\Modules\Group\Actions\AnnualUpdateSave;
use App\Modules\Group\Actions\MemberAssignRole;
use App\Modules\Group\Actions\MemberRemoveRole;
use App\Modules\Group\Actions\GroupStatusUpdate;
use App\Modules\Group\Actions\AnnualUpdateCreate;
use App\Modules\Group\Actions\AnnualUpdateSubmit;
use App\Modules\Group\Actions\EvidenceSummaryAdd;
use App\Modules\Group\Actions\AttestationGcepStore;
use App\Modules\Group\Actions\DevFakePilotApproved;
use App\Http\Controllers\Api\AnnualUpdateController;
use App\Modules\Group\Actions\ApplicationSubmitStep;
use App\Modules\Group\Actions\AttestationNhgriStore;
use App\Modules\Group\Actions\EvidenceSummaryDelete;
use App\Modules\Group\Actions\EvidenceSummaryUpdate;
use App\Modules\Group\Actions\ExpertPanelNameUpdate;
use App\Modules\Group\Actions\ApplicationSaveChanges;
use App\Modules\Group\Actions\MemberGrantPermissions;
use App\Modules\Group\Actions\MemberRevokePermission;
use App\Modules\Group\Actions\ScopeDescriptionUpdate;
use App\Modules\Group\Actions\AttestationReanalysisStore;
use App\Modules\Group\Actions\ApplicationSubmissionReject;
use App\Modules\Group\Actions\MembershipDescriptionUpdate;
use App\Modules\Group\Actions\CurationReviewProtocolUpdate;
use App\Modules\Group\Http\Controllers\Api\GroupController;
use App\Modules\Group\Actions\ExpertPanelAffiliationIdUpdate;
use App\Modules\Group\Actions\SustainedCurationReviewComplete;
use App\Modules\Group\Http\Controllers\Api\GeneListController;
use App\Modules\Group\Http\Controllers\Api\ActivityLogsController;
use App\Modules\Group\Http\Controllers\Api\GroupRelationsController;
use App\Modules\Group\Http\Controllers\Api\EvidenceSummaryController;
use App\Modules\Group\Http\Controllers\Api\GroupSubmissionsController;

Route::group([
    'prefix' => 'api/groups',
    'middleware' => ['api', 'auth:sanctum']
], function () {
    Route::get('/', [GroupController::class, 'index']);
    Route::post('/', GroupCreate::class);

    Route::post('/{group:uuid}/dev/fake-pilot-approved', DevFakePilotApproved::class);



    Route::group(['prefix' => '{group:uuid}/documents'], function () {
        Route::get('/', [GroupRelationsController::class, 'documents']);
        Route::post('/', DocumentAdd::class);
        Route::put('/{document:uuid}', DocumentUpdate::class);
    });
    
    Route::get('/{group:uuid}/children', [GroupRelationsController::class, 'children']);


    Route::get('/{group:uuid}/next-actions', [GroupRelationsController::class, 'nextActions']);
    Route::get('/{group:uuid}/tasks', [GroupRelationsController::class, 'tasks']);
    
    Route::get('/{group:uuid}', [GroupController::class, 'show']);
    Route::delete('/{group:uuid}', GroupDelete::class);
    Route::put('/{group:uuid}/parent', ParentUpdate::class);
    Route::put('/{group:uuid}/name', GroupNameUpdate::class);
    Route::put('/{group:uuid}/status', GroupStatusUpdate::class);

    // ACTIVITY LOGS
    Route::group(['prefix' => '/{uuid}/activity-logs'], function () {
        Route::get('/', [ActivityLogsController::class, 'index']);
        Route::post('/', [ActivityLogsController::class, 'store']);
        Route::put('/{logEntryId}', [ActivityLogsController::class, 'update']);
        Route::delete('/{logEntryId}', [ActivityLogsController::class, 'destroy']);
    });

    Route::put('/{group:uuid}/application/', ApplicationSaveChanges::class);
    Route::get('/{group:uuid}/application/submission', [GroupSubmissionsController::class, 'index']);
    Route::post('/{group:uuid}/application/submission', ApplicationSubmitStep::class);
    Route::post('/{group:uuid}/application/submission/{submission}/rejection', ApplicationSubmissionReject::class);

    // MEMBERS
    Route::group(['prefix' => '/{group:uuid}/members'], function () {
        Route::get('/', [GroupController::class, 'members']);
        Route::post('/', MemberAdd::class);
        Route::delete('/{member_id}', MemberRemove::class);
        Route::put('/{member_id}', MemberUpdate::class);
        Route::post('/{member_id}/retire', MemberRetire::class);
        Route::post('/{member_id}/unretire', MemberUnretire::class);
        
        Route::post('/{member_id}/roles', MemberAssignRole::class);
        Route::delete('/{member_id}/roles/{role_id}', MemberRemoveRole::class);
    
        Route::post('/{member_id}/permissions', MemberGrantPermissions::class);
        Route::delete('/{member_id}/permissions/{permission_id}', MemberRevokePermission::class);
    });

    // EXPERT PANEL INFO
    Route::group(['prefix' => '/{group:uuid}/expert-panel'], function () {
        Route::put('/curation-review-protocols', CurationReviewProtocolUpdate::class);
        Route::put('/membership-description', MembershipDescriptionUpdate::class);
        Route::put('/name', ExpertPanelNameUpdate::class);
        Route::put('/scope-description', ScopeDescriptionUpdate::class);
        Route::put('/affiliation-id', ExpertPanelAffiliationIdUpdate::class);
        Route::put('/sustained-curation-reviews', SustainedCurationReviewComplete::class);

        // ATTESTATIONS
        Route::post('/attestations/nhgri', AttestationNhgriStore::class);
        Route::post('/attestations/reanalysis', AttestationReanalysisStore::class);
        Route::post('/attestations/gcep', AttestationGcepStore::class);

        // GENES
        Route::group(['prefix' => '/genes'], function () {
            Route::get('/', [GeneListController::class, 'index']);
            Route::post('/', GenesAdd::class);
            Route::put('/{gene_id}', GeneUpdate::class);
            Route::delete('/{gene_id}', GeneRemove::class);
        });
    
        // EVIDENCE SUMMARIES
        Route::group(['prefix' => '/evidence-summaries'], function () {
            Route::get('/', [EvidenceSummaryController::class, 'index']);
            Route::post('/', EvidenceSummaryAdd::class);
            Route::put('/{summaryId}', EvidenceSummaryUpdate::class);
            Route::delete('/{summaryId}', EvidenceSummaryDelete::class);
        });

        // ANNUAL UPDATES
        Route::group(['prefix' => '/annual-updates'], function () {
            Route::get('/', [AnnualUpdateController::class, 'showLatestForGroup']);
            Route::post('/', AnnualUpdateCreate::class);
            Route::put('/{review}', AnnualUpdateSave::class);
            Route::post('/{review}', AnnualUpdateSubmit::class);
        });
    });

    Route::post('/{uuid}/invites', MemberInvite::class);
});
