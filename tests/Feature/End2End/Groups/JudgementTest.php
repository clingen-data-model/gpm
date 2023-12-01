<?php

namespace Tests\Feature\End2End\Groups;

use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\Group\Actions\ApplicationSubmitStep;
use App\Modules\Group\Actions\JudgementCreate;
use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\Judgement;
use App\Modules\Person\Models\Person;
use Database\Seeders\NextActionAssigneesTableSeeder;
use Database\Seeders\NextActionTypesTableSeeder;
use Database\Seeders\SubmissionTypeAndStatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

abstract class JudgementTest extends TestCase
{
    use RefreshDatabase;

    public function setup(): void
    {
        parent::setup();
        $this->setupForGroupTest();
        $this->seed(SubmissionTypeAndStatusSeeder::class);
        $this->seed(NextActionAssigneesTableSeeder::class);
        $this->seed(NextActionTypesTableSeeder::class);
        $this->submit = app()->make(ApplicationSubmitStep::class);
        $this->makeJudgement = app()->make(JudgementCreate::class);

        $this->user = $this->setupUserWithPerson(permissions: ['ep-applications-approve']);
        $epAndSub = $this->setupExpertPanelAndSubmission();
        $this->expertPanel = $epAndSub['expertPanel'];
        $this->submission = $epAndSub['submission'];
        Sanctum::actingAs($this->user);
    }

    protected function setupExpertPanelAndSubmission($expertPanel = null, $submitter = null): array
    {
        $expertPanel = $expertPanel ?? ExpertPanel::factory()->vcep()->create();
        $submitter = $submitter ?? Person::factory()->create();
        $submission = $this->submit->handle(group: $expertPanel->group, submitter: $submitter);

        return ['expertPanel' => $expertPanel, 'submission' => $submission];
    }

    protected function setupJudgement(Group $group): Judgement
    {
        return $this->makeJudgement->handle(
            group: $group,
            person: $this->user->person,
            decision: 'request-revisions',
            notes: 'blah blah blah'
        );
    }
}
