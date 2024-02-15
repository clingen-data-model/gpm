<?php

namespace Tests\Feature\End2End;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Testing\TestResponse;
use App\Modules\Person\Models\Person;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Actions\StepApprove;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Modules\Group\Actions\ApplicationSubmitStep;
use Database\Seeders\NextActionAssigneesTableSeeder;
use Database\Seeders\NextActionTypesTableSeeder;
use Database\Seeders\SubmissionTypeAndStatusSeeder;

class ApplicationActivityTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->setupForGroupTest();
        $this->seed(SubmissionTypeAndStatusSeeder::class);
        $this->seed(NextActionAssigneesTableSeeder::class);
        $this->seed(NextActionTypesTableSeeder::class);
        $this->submit = app()->make(ApplicationSubmitStep::class);

        $this->user = $this->setupUser();
        Sanctum::actingAs($this->user);

    }

    /**
     * @test
     */
    public function user_without_permission_unauthorized()
    {
        $this->makeRequest()
            ->assertStatus(403);
    }

    /**
     * @test
     */
    public function application_managers_see_submitted_applications_not_completed()
    {
        $this->setupPermission(['ep-applications-manage']);
        $this->user->givePermissionTo('ep-applications-manage');

        $this->setupAllEpsWithSubmissions();

        $this->makeRequest()
            ->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /**
     * @test
     */
    public function application_commenters_see_submitted_applications_that_are_not_completed()
    {
        $this->setupPermission(['ep-applications-comment']);
        $this->user->givePermissionTo('ep-applications-comment');

        $this->setupAllEpsWithSubmissions();

        $rsp = $this->makeRequest();
        // dump($rsp->original);
        $rsp->assertStatus(200);
        $rsp->assertJsonCount(2, 'data');
    }

    /**
     * @test
     */
    public function application_approvers_only_see_applications_under_chair_review()
    {
        $this->setupPermission(['ep-applications-approve', 'ep-applications-comment']);
        $this->user->givePermissionTo(['ep-applications-approve', 'ep-applications-comment']);

        $this->setupAllEpsWithSubmissions();

        $this->makeRequest()
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    private function setupAllEpsWithSubmissions()
    {
        $ep = ExpertPanel::factory()->create();
        $epAndSub1 = $this->setupExpertPanelAndSubmission();
        $epAndSub2 = $this->setupExpertPanelAndSubmission();
        $epAndSub2['submission']->update([
            'submission_status_id' => config('submissions.statuses.under-chair-review.id')
        ]);
        $epAndSub3 = $this->setupExpertPanelAndSubmission();
        $epAndSub3['submission']->update([
            'submission_status_id' => config('submissions.statuses.revisions-requested.id')
        ]);
        $epAndSub4 = $this->setupExpertPanelAndSubmission();
        $epAndSub4['submission']->update([
            'submission_status_id' => config('submissions.statuses.approved.id')
        ]);
        $epAndSubApproved = $this->setupExpertPanelAndSubmission();
        $epAndSubApproved['submission']->update([
            'submission_status_id' => config('submissions.statuses.revisions-requested.id')
        ]);
        app()->make(StepApprove::class)->handle($epAndSubApproved['expertPanel'], \Carbon\Carbon::now());
        app()->make(StepApprove::class)->handle($epAndSubApproved['expertPanel'], \Carbon\Carbon::now());
        app()->make(StepApprove::class)->handle($epAndSubApproved['expertPanel'], \Carbon\Carbon::now());
        app()->make(StepApprove::class)->handle($epAndSubApproved['expertPanel'], \Carbon\Carbon::now());

        return [$epAndSub1, $epAndSub2, $epAndSub3, $epAndSub4, $epAndSubApproved];
    }


    private function setupExpertPanelAndSubmission($expertPanel = null, $submitter = null): array
    {
        $expertPanel = $expertPanel ?? ExpertPanel::factory()->vcep()->create();
        $submitter = $submitter ?? Person::factory()->create();
        $submission = $this->submit->handle(group: $expertPanel->group, submitter: $submitter);

        return ['expertPanel' => $expertPanel, 'submission' => $submission];
    }



    private function makeRequest(): TestResponse
    {
        return $this->json('GET', '/api/groups/applications');
    }



}
