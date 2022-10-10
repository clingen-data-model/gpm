<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Modules\Group\Models\Group;
use App\Modules\Person\Models\Person;
use App\Modules\Group\Models\Judgement;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Database\Seeders\NextActionTypesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\SubmissionTypeAndStatusSeeder;
use App\Modules\Group\Actions\ApplicationSubmitStep;
use Database\Seeders\NextActionAssigneesTableSeeder;
use App\Modules\Group\Actions\JudgementCreate;
use App\Modules\Group\Actions\ApplicationSendToChairs;
use App\Modules\Group\Actions\ApplicationSubmissionRemindChairs;
use App\Modules\Group\Notifications\ApprovalReminderNotification;

class ApplicationSubmissionRemindChairsTest extends TestCase
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
        $this->sendToChairs = app()->make(ApplicationSendToChairs::class);
        $this->makeJudgement = app()->make(JudgementCreate::class);
        $this->remindChairs = app()->make(ApplicationSubmissionRemindChairs::class);

        $epAndSub = $this->setupExpertPanelAndSubmission();
        $this->expertPanel = $epAndSub['expertPanel'];
        $this->submission = $epAndSub['submission'];

        $this->approver1 = $this->setupUserWithPerson(permissions: ['ep-applications-approve']);
        $this->approver2 = $this->setupUserWithPerson(permissions: ['ep-applications-approve']);
        $this->user = $this->setupUserWithPerson(permissions: ['ep-appications-manage']);

        $this->sendToChairs->handle($this->expertPanel->group);
    }

    /**
     * @test
     */
    public function sends_email_to_approvers_who_have_not_made_a_judgement()
    {
        $epAndSub = $this->setupExpertPanelAndSubmission();
        $ep2 = $epAndSub['expertPanel'];
        $submission2 = $epAndSub['submission'];
        $this->sendToChairs->handle($ep2->group);

        $this->setupJudgement($this->expertPanel->group, $this->approver1->person);
        $this->setupJudgement($ep2->group, $this->approver1->person);

        Notification::fake();

        $this->remindChairs->handle();

        Notification::assertNotSentTo($this->approver1->person, ApprovalReminderNotification::class);
        Notification::assertNotSentTo($this->user->person, ApprovalReminderNotification::class);
        Notification::assertSentTo(
            $this->approver2->person,
            ApprovalReminderNotification::class,
            function ($notification) use ($submission2) {
                return $notification->waitingSubmissions->count() == 2
                 && $notification->waitingSubmissions->pluck('id')->toArray() == [$this->submission->id, $submission2->id];
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
