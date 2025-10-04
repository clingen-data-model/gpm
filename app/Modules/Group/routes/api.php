<?php

use App\Modules\Group\Models\Group;
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
use App\Modules\Group\Actions\JudgementCreate;
use App\Modules\Group\Actions\JudgementDelete;
use App\Modules\Group\Actions\JudgementUpdate;
use App\Modules\Group\Actions\AnnualUpdateSave;
use App\Modules\Group\Actions\MemberAssignRole;
use App\Modules\Group\Actions\MemberRemoveRole;
use App\Modules\Group\Actions\GroupStatusUpdate;
use App\Modules\Group\Actions\AnnualUpdateCreate;
use App\Modules\Group\Actions\AnnualUpdateSubmit;
use App\Modules\Group\Actions\EvidenceSummaryAdd;
use App\Modules\Group\Actions\HandleGroupCommand;
use App\Modules\Group\Actions\AttestationGcepStore;
use App\Modules\Group\Actions\DevFakePilotApproved;
use App\Modules\Group\Actions\GroupAttributesUpdate;
use App\Http\Controllers\Api\AnnualUpdateController;
use App\Modules\Group\Actions\ApplicationSubmitStep;
use App\Modules\Group\Actions\AttestationNhgriStore;
use App\Modules\Group\Actions\EvidenceSummaryDelete;
use App\Modules\Group\Actions\EvidenceSummaryUpdate;
use App\Modules\Group\Actions\ExpertPanelNameUpdate;
use App\Modules\ExpertPanel\Actions\CoiResponseStore;
use App\Modules\Group\Actions\ApplicationActivityGet;
use App\Modules\Group\Actions\ApplicationSaveChanges;
use App\Modules\Group\Actions\MemberGrantPermissions;
use App\Modules\Group\Actions\MemberRevokePermission;
use App\Modules\Group\Actions\ScopeDescriptionUpdate;
use App\Modules\ExpertPanel\Actions\SpecificationsGet;
use App\Modules\Group\Actions\AttestationReanalysisStore;
use App\Modules\Group\Actions\ApplicationSubmissionReject;
use App\Modules\Group\Actions\MembershipDescriptionUpdate;
use App\Modules\Group\Http\Resources\GroupExternalResource;
use App\Modules\Group\Actions\CurationReviewProtocolUpdate;
use App\Modules\Group\Http\Controllers\Api\GroupController;
use App\Modules\Group\Actions\ExpertPanelAffiliationIdUpdate;
use App\Modules\Group\Actions\SustainedCurationReviewComplete;
use App\Modules\Group\Http\Controllers\Api\GeneListController;
use App\Modules\Group\Http\Controllers\Api\ActivityLogsController;
use App\Modules\Group\Http\Controllers\Api\GroupRelationsController;
use App\Modules\ExpertPanel\Http\Controllers\Api\SimpleCoiController;
use App\Modules\Group\Http\Controllers\Api\EvidenceSummaryController;
use App\Modules\Group\Http\Controllers\Api\GroupSubmissionsController;
use App\Modules\Group\Http\Controllers\Api\GroupPublicationsController;
use App\Modules\Group\Actions\PublicationStore;
use App\Modules\Group\Actions\PublicationDelete;

Route::group([
    'prefix' => 'api',
    'middleware' => ['api', 'auth:sanctum']
], function () {
    Route::group(['prefix' => 'cois'], function () {
        Route::get('/{Coi:id}', [SimpleCoiController::class, 'show']);
    });

    Route::group(['prefix' => 'coi'], function () {
        Route::get('/{code}/group', [SimpleCoiController::class, 'getGroup']);
        Route::post('/{code}', CoiResponseStore::class);
    });
});

Route::group([
    'prefix' => 'api/groups',
    'middleware' => ['api', 'auth:sanctum']
], function () {
    Route::get('/', [GroupController::class, 'index']);
    Route::post('/', GroupCreate::class);

    Route::get('/website_summary/{uuid}', function (String $uuid) {
        return new GroupExternalResource(Group::findByUuidOrFail($uuid));
    });

    Route::get('/applications', ApplicationActivityGet::class);

    Route::group(['prefix' => '/{group:uuid}'], function () {
        Route::get('/', [GroupController::class, 'show']);
        Route::delete('/', GroupDelete::class);
        Route::put('/', GroupAttributesUpdate::class);

        // ACTIVITY LOGS
        Route::group(['prefix' => '/activity-logs'], function () {
            Route::get('/', [ActivityLogsController::class, 'index']);
            Route::post('/', [ActivityLogsController::class, 'store']);
            Route::put('/{logEntryId}', [ActivityLogsController::class, 'update']);
            Route::delete('/{logEntryId}', [ActivityLogsController::class, 'destroy']);
        });

        // APPLICATION
        Route::group(['prefix' => '/application'], function () {
            Route::put('/', ApplicationSaveChanges::class);

            Route::group(['prefix' => '/submission'], function () {
                Route::get('/', [GroupSubmissionsController::class, 'index']);
                Route::post('/', ApplicationSubmitStep::class);
                Route::post('/{submission}/rejection', ApplicationSubmissionReject::class);
            });

            Route::get('/latest-submission', [GroupSubmissionsController::class, 'latestSubmission']);

            Route::group(['prefix' => '/judgements'], function () {
                Route::post('/', JudgementCreate::class);
                Route::put('/{id}', JudgementUpdate::class);
                Route::delete('/{id}', JudgementDelete::class);
            });

        });

        Route::post('/command', HandleGroupCommand::class);

        Route::get('/children', [GroupRelationsController::class, 'children']);

        Route::post('/dev/fake-pilot-approved', DevFakePilotApproved::class);

        // DOCUMENTS
        Route::group(['prefix' => '/documents'], function () {
            Route::get('/', [GroupRelationsController::class, 'documents']);
            Route::post('/', DocumentAdd::class);
            Route::put('/{document:uuid}', DocumentUpdate::class);
        });

        // EXPERT PANEL INFO
        Route::group(['prefix' => '/expert-panel'], function () {
            Route::put('/curation-review-protocols', CurationReviewProtocolUpdate::class);
            Route::put('/membership-description', MembershipDescriptionUpdate::class);
            Route::put('/name', ExpertPanelNameUpdate::class);
            Route::put('/scope-description', ScopeDescriptionUpdate::class);
            Route::put('/affiliation-id', ExpertPanelAffiliationIdUpdate::class);
            Route::put('/sustained-curation-reviews', SustainedCurationReviewComplete::class);

            // ATTESTATIONS
            Route::group(['prefix' => '/attestations'], function () {
                Route::post('/nhgri', AttestationNhgriStore::class);
                Route::post('/reanalysis', AttestationReanalysisStore::class);
                Route::post('/gcep', AttestationGcepStore::class);
            });

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
                Route::get('/{id}', [AnnualUpdateController::class, 'showForGroup']);

                Route::post('/', AnnualUpdateCreate::class);

                Route::put('/{review}', AnnualUpdateSave::class);
                Route::post('/{review}', AnnualUpdateSubmit::class);
            });
        });

        // MEMBERS
        Route::group(['prefix' => '/members'], function () {
            Route::get('/', [GroupController::class, 'members']);
            Route::post('/', MemberAdd::class);

            Route::group(['prefix' => '/{member_id}'], function () {
                Route::delete('/', MemberRemove::class);
                Route::put('/', MemberUpdate::class);
                Route::post('/retire', MemberRetire::class);
                Route::post('/unretire', MemberUnretire::class);

                Route::group(['prefix' => '/roles'], function () {
                    Route::post('/', MemberAssignRole::class);
                    Route::delete('/{role_id}', MemberRemoveRole::class);
                });

                Route::group(['prefix' => '/permissions'], function () {
                    Route::post('/', MemberGrantPermissions::class);
                    Route::delete('/{permission_id}', MemberRevokePermission::class);
                });
            });
        });


        Route::get('/next-actions', [GroupRelationsController::class, 'nextActions']);
        Route::put('/name', GroupNameUpdate::class);
        Route::put('/parent', ParentUpdate::class);
        Route::get('/tasks', [GroupRelationsController::class, 'tasks']);
        Route::get('/specifications', SpecificationsGet::class);
        Route::put('/status', GroupStatusUpdate::class);

        // PUBLICATIONS
        Route::group(['prefix' => '/publications'], function () {
            Route::get('/', [GroupPublicationsController::class, 'index']);
            Route::post('/', PublicationStore::class);
            Route::delete('/{publication}', PublicationDelete::class);
        });

    });

    Route::post('/{uuid}/invites', MemberInvite::class);

});
