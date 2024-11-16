<?php

namespace Tests\Feature\End2End;

use Carbon\Carbon;
use Tests\TestCase;
use App\Modules\Group\Models\Group;
use App\Modules\Person\Models\Person;
use App\Modules\ExpertPanel\Models\Coi;
use App\Modules\Group\Actions\MemberAdd;
use App\Modules\Group\Models\GroupMember;
use App\Modules\Group\Actions\MemberRetire;
use Illuminate\Support\Facades\Notification;
use App\Notifications\CoiReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CoiReminderTest extends TestCase
{
    private $group;
    private $user1;
    private $membership1;
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->setupForGroupTest();

        $this->group = Group::factory()->create(['group_status_id' => config('groups.statuses.active.id')]);
        $this->user1 = $this->setupUserWithPerson();
        $this->membership1 = app()->make(MemberAdd::class)->handle($this->group, $this->user1->person);
    }

    /**
     * @test
     */
    public function groupMember_can_scope_by_hasPendingCois()
    {
        $user2 = $this->setupUserWithPerson();
        $groupMember = app()->make(MemberAdd::class)->handle($this->group, $user2->person);
        Coi::factory()->create([
            'group_member_id' => $groupMember->id,
            'group_id' => $this->group->id,
            'completed_at' => Carbon::now()->subDays(364)
        ]);
        $this->assertEquals(1, GroupMember::hasPendingCoi()->count());
    }

    /**
     * @test
     */
    public function person_can_scope_by_hasPendingCois()
    {
        $user2 = $this->setupUserWithPerson();
        $groupMember = app()->make(MemberAdd::class)->handle($this->group, $user2->person);
        Coi::factory()->create([
            'group_member_id' => $groupMember->id,
            'group_id' => $this->group->id,
            'completed_at' => Carbon::now()->subDays(364)
        ]);
        $peopleWithPendingCois = Person::hasPendingCois()->get();
        $this->assertEquals(1, $peopleWithPendingCois->count());
        $this->assertEquals($this->user1->person->id, $peopleWithPendingCois->first()->id);
    }

    /**
     * @test
     */
    public function person_related_to_memberships_with_pending_coi()
    {
        $group2 = Group::factory()->create();
        $group3 = Group::factory()->create();
        $membership2 = app()->make(MemberAdd::class)->handle($group2, $this->user1->person);
        $membership3 = app()->make(MemberAdd::class)->handle($group3, $this->user1->person);
        app()->make(MemberRetire::class)->handle($membership3, Carbon::today());
        Coi::factory()->create([
            'group_member_id' => $membership2->id,
            'group_id' => $group2->id,
            'completed_at' => Carbon::now()->subDays(364)
        ]);

        $membershipsWithPendingCoi = $this->user1->person->membershipsWithPendingCoi;
        $this->assertEquals(1, $membershipsWithPendingCoi->count());
        $this->assertEquals($this->membership1->id, $membershipsWithPendingCoi->first()->id);
    }

    /**
     * @test
     */
    public function sends_notification_to_activited_person_with_pending_coi()
    {
        $user2 = $this->setupUserWithPerson();
        $groupMember = app()->make(MemberAdd::class)->handle($this->group, $user2->person);
        Coi::factory()->create([
            'group_member_id' => $groupMember->id,
            'group_id' => $this->group->id,
            'completed_at' => Carbon::now()->subDays(364)
        ]);

        $group2 = Group::factory()->create();
        $membership2 = app()->make(MemberAdd::class)->handle($group2, $this->user1->person);
        Coi::factory()->create([
            'group_member_id' => $membership2->id,
            'group_id' => $group2->id,
            'completed_at' => Carbon::now()->subDays(364)
        ]);

        Notification::fake();
        $this->triggerScheduledTask();
        Notification::assertSentTo($this->user1->person, CoiReminderNotification::class);
        Notification::assertNotSentTo($user2->person, CoiReminderNotification::class);
    }

    /**
     * @test
     */
    public function only_sends_notifications_on_monday_at_6am()
    {
        Carbon::setTestNow('2022-02-14 06:01:00');

        Notification::fake();
        $this->artisan('schedule:run');
        Notification::assertNotSentTo($this->user1->person, CoiReminderNotification::class);
    }

    /**
     * @test
     */
    public function does_not_send_email_for_inactive_memberships()
    {
        $group2 = Group::factory()->create();
        app()->make(MemberRetire::class)->handle($this->membership1, Carbon::today()->subDays(2));

        Notification::fake();
        $this->triggerScheduledTask();
        Notification::assertNothingSent();
    }

    /**
     * @test
     */
    public function sends_email_to_members_of_non_eps()
    {
        $user = $this->setupUserWithPerson();
        $cdwg = Group::factory()->cdwg()->create();
        $wg = Group::factory()->wg()->create();
        app()->make(MemberAdd::class)->handle($cdwg, $user->person);
        app()->make(MemberAdd::class)->handle($wg, $user->person);

        Notification::fake();
        $this->triggerScheduledTask();
        Notification::assertSentTo($user->person, CoiReminderNotification::class);
    }

    /**
     * @test
     */
    public function does_not_send_coi_reminder_to_nonactivated_people()
    {
        $person = Person::factory()->create();
        app()->make(MemberAdd::class)->handle($this->group, $person);

        Notification::fake();
        $this->triggerScheduledTask();
        Notification::assertNotSentTo($person, CoiReminderNotification::class);

    }


    private function triggerScheduledTask()
    {
        Carbon::setTestNow('2022-02-14 06:00:00');
        $this->artisan('schedule:run');
    }
}
