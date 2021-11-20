<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Group\Actions\GenesAdd;
use App\Modules\Group\Actions\MemberAdd;
use App\Modules\Group\Actions\GeneRemove;
use App\Modules\Group\Actions\GeneUpdate;
use App\Modules\Group\Actions\MemberInvite;
use App\Modules\Group\Actions\MemberRemove;
use App\Modules\Group\Actions\MemberRetire;
use App\Modules\Group\Actions\MemberUpdate;
use App\Modules\Group\Actions\AddGenesToVcep;
use App\Modules\Group\Actions\MemberAssignRole;
use App\Modules\Group\Actions\MemberRemoveRole;
use App\Modules\Group\Actions\EvidenceSummaryAdd;
use App\Modules\Group\Actions\AttestationGcepStore;
use App\Modules\Group\Actions\AttestationNhgriStore;
use App\Modules\Group\Actions\EvidenceSummaryDelete;
use App\Modules\Group\Actions\EvidenceSummaryUpdate;
use App\Modules\Group\Actions\NhgriAttestationStore;
use App\Modules\Group\Actions\MemberGrantPermissions;
use App\Modules\Group\Actions\MemberRevokePermission;
use App\Modules\Group\Actions\ScopeDescriptionUpdate;
use App\Modules\Group\Actions\AttestationReanalysisStore;
use App\Modules\Group\Actions\ReanalysisAttestationStore;
use App\Modules\Group\Actions\MembershipDescriptionUpdate;
use App\Modules\Group\Actions\CurationReviewProtocolUpdate;
use App\Modules\Group\Actions\ExpertPanelNameUpdate;
use App\Modules\Group\Actions\ParentUpdate;
use App\Modules\Group\Http\Controllers\Api\GroupController;
use App\Modules\Group\Http\Controllers\Api\GeneListController;
use App\Modules\Group\Http\Controllers\Api\ActivityLogsController;
use App\Modules\Group\Http\Controllers\Api\EvidenceSummaryController;

// Route::post('/{{group_uuid}}/members', MemberAdd::class);



Route::group([
    'prefix' => 'api/groups',
    'middleware' => ['api', 'auth:sanctum']
], function () {
    Route::get('/', [GroupController::class, 'index']);
    Route::get('/{uuid}', [GroupController::class, 'show']);
    Route::put('/{group:uuid}/parent', ParentUpdate::class);

    // ACTIVITY LOGS
    Route::group(['prefix' => '/{uuid}/activity-logs'], function () {
        Route::get('/', [ActivityLogsController::class, 'index']);
        Route::post('/', [ActivityLogsController::class, 'store']);
        Route::put('/{logEntryId}', [ActivityLogsController::class, 'update']);
        Route::delete('/{logEntryId}', [ActivityLogsController::class, 'destroy']);
    });

    // APPLICATION - DEPRECATED (USE {uuid}/expert-panel)
    Route::group(['prefix' => '/{uuid}/application'], function () {
        Route::put('/membership-description', MembershipDescriptionUpdate::class);
        Route::put('/scope-description', ScopeDescriptionUpdate::class);
        Route::put('/curation-review-protocols', CurationReviewProtocolUpdate::class);

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
    });

    // MEMBERS
    Route::group(['prefix' => '/{uuid}/members'], function () {
        Route::get('/', [GroupController::class, 'members']);
        Route::post('/', MemberAdd::class);
        Route::delete('/{member_id}', MemberRemove::class);
        Route::put('/{member_id}', MemberUpdate::class);
        Route::post('/{member_id}/retire', MemberRetire::class);
        
        Route::post('/{member_id}/roles', MemberAssignRole::class);
        Route::delete('/{member_id}/roles/{role_id}', MemberRemoveRole::class);
    
        Route::post('/{member_id}/permissions', MemberGrantPermissions::class);
        Route::delete('/{member_id}/permissions/{permission_id}', MemberRevokePermission::class);
    });

    // EXPERT PANEL INFO
    Route::group(['prefix' => '/{uuid}/expert-panel'], function () {
        Route::put('/curation-review-protocols', CurationReviewProtocolUpdate::class);
        Route::put('/membership-description', MembershipDescriptionUpdate::class);
        Route::put('/name', ExpertPanelNameUpdate::class);
        Route::put('/scope-description', ScopeDescriptionUpdate::class);

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
    });

    Route::post('/{uuid}/invites', MemberInvite::class);
});
