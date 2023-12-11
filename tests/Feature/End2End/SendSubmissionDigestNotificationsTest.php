<?php

namespace Tests\Feature\End2End;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Comment;
use App\Modules\Person\Models\Person;
use App\Modules\Group\Models\Judgement;
use App\Modules\Group\Models\SubmissionType;
use Illuminate\Support\Facades\Notification;
use App\Modules\Group\Models\SubmissionStatus;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Notifications\ApprovalDigestNotification;
use App\Actions\SendSubmissionDigestNotifications;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Modules\Group\Notifications\ApprovalReminder;
use App\Notifications\UserDefinedDatabaseNotification;
use App\Modules\Group\Notifications\CommentActivityNotification;
use App\Modules\Group\Notifications\JudgementActivityNotification;

class SendSubmissionDigestNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->setupForGroupTest();
        
        SubmissionStatus::factory()->create();
        SubmissionType::factory()->create();
    }

    /**
     * @test
     */
    public function does_not_send_notification_if_no_unsent_digestible_submission_notifications()
    {
        $person = Person::factory()->create();

        Notification::fake();

        (new SendSubmissionDigestNotifications)->handle();

        Notification::assertNothingSent();
    }

    /**
     * @test
     */
    public function sends_notification_if_unsent_digestible_submission_notifications()
    {


        
        $group = ExpertPanel::factory()->create()->group;
        $comment = Comment::factory()->create(['subject_type' => get_class($group), 'subject_id' => $group->id]);
        $judgement = Judgement::factory()->create();

        $person = Person::factory()->create();
        Notification::send($person, new CommentActivityNotification($group, $comment, 'created'));
        Notification::send($person, new CommentActivityNotification($group, $comment, 'updated'));
        Notification::send($person, new JudgementActivityNotification($group, $judgement, 'created'));
        Notification::send($person, new ApprovalReminder($group, $judgement->submission, $person));

        Notification::fake();

        (new SendSubmissionDigestNotifications)->handle();

        Notification::assertSentTo(
            $person,
            ApprovalDigestNotification::class,
            function ($notification) {
                return $notification->approvalDigestNotifications
                            ->get(CommentActivityNotification::class)
                            ->count() == 1
                        && $notification->approvalDigestNotifications
                            ->get(JudgementActivityNotification::class)
                            ->count() == 1
                        && $notification->approvalDigestNotifications
                            ->get(ApprovalReminder::class)
                            ->count() == 1
                        ;
            }
        );
    }


    /**
     * @test
     */
    public function marks_all_submission_notifications_read()
    {
        $group = ExpertPanel::factory()->create()->group;
        $comment = Comment::factory()->create(['subject_type' => get_class($group), 'subject_id' => $group->id]);
        $judgement = Judgement::factory()->create();

        $person = Person::factory()->create();
        Notification::send($person, new CommentActivityNotification($group, $comment, 'created'));
        Notification::send($person, new CommentActivityNotification($group, $comment, 'updated'));
        Notification::send($person, new JudgementActivityNotification($group, $judgement, 'created'));
        Notification::send($person, new ApprovalReminder($group, $judgement->submission, $person));

        (new SendSubmissionDigestNotifications)->handle();

        $this->assertEquals(0, $person->unreadNotifications->count());
    }


    /**
     * @test
     */
    public function does_not_include_non_digestible_notifications()
    {
        $group = ExpertPanel::factory()->create()->group;
        $comment = Comment::factory()->create(['subject_type' => get_class($group), 'subject_id' => $group->id]);

        $person = Person::factory()->create();
        Notification::send($person, new UserDefinedDatabaseNotification('test test test'));
        Notification::send($person, new CommentActivityNotification($group, $comment, 'created'));

        Notification::fake();

        (new SendSubmissionDigestNotifications)->handle();

        Notification::assertNotSentTo(
            $person,
            ApprovalDigestNotification::class,
            function ($notification) {
                return $notification->approvalDigestNotifications
                    ->pluck('type')
                    ->contains(UserDefinedDatabaseNotification::class);
            }
        );
    }

    /**
     * @test
     */
    public function correctly_renders_email()
    {
        $group = ExpertPanel::factory()->create()->group;
        $comment1 = Comment::factory()->create(['subject_type' => get_class($group), 'subject_id' => $group->id]);
        $comment2 = Comment::factory()->create(['subject_type' => get_class($group), 'subject_id' => $group->id]);
        $judgement = Judgement::factory()->create();
        $judgement2 = Judgement::factory()->create();

        $person = Person::factory()->create();
        Notification::send($person, new CommentActivityNotification($group, $comment1, 'created'));
        Notification::send($person, new CommentActivityNotification($group, $comment2, 'created'));
        Notification::send($person, new JudgementActivityNotification($group, $judgement, 'created'));
        Notification::send($person, new JudgementActivityNotification($group, $judgement2, 'created'));
        Notification::send($person, new ApprovalReminder($group, $judgement->submission, $person));

        $digestNotification = new ApprovalDigestNotification($person->unreadNotifications->groupBy('type'));

        $renderedHtml = $digestNotification->toMail($person)->render();

        $this->assertStringContainsString('There is an application awaiting your decision:', $renderedHtml);
        $this->assertStringContainsString(
            stripTagsAndSpaces($group->display_name.' - Step 1'),
            stripTagsAndSpaces($renderedHtml)
        );

        $this->assertStringContainsString('New comments have been made on applications pending approval:', $renderedHtml);
        $this->assertStringContainsString(
            stripTagsAndSpaces($group->display_name).":2",
            stripTagsAndSpaces($renderedHtml)
        );

        $this->assertStringContainsString('New judgements have been made for applications:', $renderedHtml);
        $this->assertStringContainsString(
            stripTagsAndSpaces($group->display_name).":".stripTagsAndSpaces($judgement->person->name),
            stripTagsAndSpaces($renderedHtml)
        );

    }

    /**
     * @test
     */
    public function approval_digests_sent_on_monday_at_610_am()
    {
       ['person' => $person] = $this->setupForScheduleTest();

        Carbon::setTestnow('monday at 6:10 am');
        Notification::fake();
        $this->artisan('schedule:run');
        Notification::assertSentTo($person, ApprovalDigestNotification::class);
    }

    /**
     * @test
     */
    public function approval_digests_sent_on_thursday_at_610_am()
    {
        ['person' => $person] = $this->setupForScheduleTest();

        Carbon::setTestnow('thursday at 6:10 am');
        Notification::fake();
        $this->artisan('schedule:run');
        Notification::assertSentTo($person, ApprovalDigestNotification::class);
    }


    /**
     * @test
     */
    public function does_not_send_any_other_day()
    {
        $this->setupForScheduleTest();

        Notification::fake();
        foreach (['tuesday', 'wednesday', 'friday', 'saturday', 'sunday'] as $day) {
            Carbon::setTestnow($day.' at 6:10 am');
            $this->artisan('schedule:run');
            Notification::assertNothingSent(ApprovalDigestNotification::class);
        }
    }


    private function setupForScheduleTest()
    {
        $this->setupPermission(['ep-applications-approve', 'ep-applications-comment']);
        $group = ExpertPanel::factory()->create()->group;
        $comment = Comment::factory()->create(['subject_type' => get_class($group), 'subject_id' => $group->id]);

        $person = Person::factory()->create();
        Notification::send($person, new CommentActivityNotification($group, $comment, 'created'));

        return compact('person', 'group', 'comment');
    }

}
