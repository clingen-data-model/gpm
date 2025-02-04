<?php

namespace Tests\Feature\End2End\Group;

use Carbon\Carbon;
use Tests\TestCase;
use App\Modules\Group\Models\Group;
use App\Modules\Person\Models\Person;
use App\Modules\Group\Models\Judgement;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use App\Modules\Group\Actions\JudgementCreate;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Database\Seeders\NextActionTypesTableSeeder;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Database\Seeders\SubmissionTypeAndStatusSeeder;
use App\Modules\Group\Actions\ApplicationSubmitStep;
use Database\Seeders\NextActionAssigneesTableSeeder;
use App\Modules\Group\Notifications\ApprovalReminder;
use App\Modules\Group\Actions\ApplicationSendToChairs;
use App\Modules\Group\Actions\SubmissionApprovalRemindersCreate;
use App\Modules\Group\Actions\SubmissionApprovalRemeindersCreate;

class SubmissionApprovalRemindersCreateTest extends TestCase
{
    use FastRefreshDatabase;

    public function setup():void
    {
        parent::setup();

        // Seed data necessary for test and side-effects.
        $this->setupForGroupTest();
        $this->seed(SubmissionTypeAndStatusSeeder::class);
        $this->seed(NextActionAssigneesTableSeeder::class);
        $this->seed(NextActionTypesTableSeeder::class);

        // Actions needed to setup and run the test.
        $this->submit = app()->make(ApplicationSubmitStep::class);
        $this->sendToChairs = app()->make(ApplicationSendToChairs::class);
        $this->makeJudgement = app()->make(JudgementCreate::class);

        $epAndSub = $this->setupExpertPanelAndSubmission();
        $this->expertPanel = $epAndSub['expertPanel'];
        $this->submission = $epAndSub['submission'];

        $this->approver1 = $this->setupUserWithPerson(permissions: ['ep-applications-approve']);
        $this->approver2 = $this->setupUserWithPerson(permissions: ['ep-applications-approve']);
        $this->user = $this->setupUserWithPerson(permissions: ['ep-applications-manage']);

        $this->sendToChairs->handle($this->expertPanel->group);
    }


    /**
     * @test
     */
    public function creates_ApprovalReminderNotification_only_for_truant_approvers()
    {
        $epAndSub = $this->setupExpertPanelAndSubmission();
        $ep2 = $epAndSub['expertPanel'];
        $submission2 = $epAndSub['submission'];
        $this->sendToChairs->handle($ep2->group);

        $this->setupJudgement($this->expertPanel->group, $this->approver1->person);
        $this->setupJudgement($ep2->group, $this->approver1->person);

        Notification::fake();

        Carbon::setTestNow('monday at 6:10 am');

        $this->artisan('schedule:run');

        Notification::assertNotSentTo($this->approver1->person, ApprovalReminder::class);
        Notification::assertNotSentTo($this->user->person, ApprovalReminder::class);
        Notification::assertSentTo(
            $this->approver2->person,
            ApprovalReminder::class,
            function ($notification) use ($submission2) {
                return $notification->group->id == $submission2->group_id
                    && $notification->submission->id == $submission2->id
                    && $notification->approver->id == $this->approver2->person->id;
            }
        );
    }



    protected function setupExpertPanelAndSubmission($expertPanel = null, $submitter = null): array
    {
        $expertPanel = $expertPanel ?? ExpertPanel::factory()->vcep()->create();
        $submitter = $submitter ?? Person::factory()->create();
        $submission = $this->submit->handle(group: $expertPanel->group, submitter: $submitter);

        return ['expertPanel' => $expertPanel, 'submission' => $submission];
    }

    protected function setupJudgement(Group $group, Person $approver): Judgement
    {
        return $this->makeJudgement->handle(
            group: $group,
            person: $approver,
            decision: 'request-revisions',
            notes: 'blah blah blah'
        );

    }



}
