<?php

namespace Tests\Feature\End2End;

use Carbon\Carbon;
use Tests\TestCase;
use App\Modules\Person\Models\Person;
use App\Modules\ExpertPanel\Models\Coi;
use App\Modules\Group\Actions\MemberAdd;
use App\Modules\Group\Models\GroupMember;
use App\Modules\Group\Actions\MemberRetire;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use App\Notifications\CoiReminderNotification;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CoiReminderTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->setupForGroupTest();
        
        $this->ep = ExpertPanel::factory()->create();
        $this->user1 = $this->setupUserWithPerson();
        $this->membership1 = app()->make(MemberAdd::class)->handle($this->ep->group, $this->user1->person);
        config(['app.features.coi_reminders' => true]);
    }

    /**
     * @test
     */
    public function groupMember_can_scope_by_hasPendingCois()
    {
        $user2 = $this->setupUserWithPerson();
        $groupMember = app()->make(MemberAdd::class)->handle($this->ep->group, $user2->person);
        Coi::factory()->create([
            'group_member_id' => $groupMember->id,
            'expert_panel_id' => $this->ep->id,
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
        $groupMember = app()->make(MemberAdd::class)->handle($this->ep->group, $user2->person);
        Coi::factory()->create([
            'group_member_id' => $groupMember->id,
            'expert_panel_id' => $this->ep->id,
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
        $ep2 = ExpertPanel::factory()->create();
        $ep3 = ExpertPanel::factory()->create();
        $membership2 = app()->make(MemberAdd::class)->handle($ep2->group, $this->user1->person);
        $membership3 = app()->make(MemberAdd::class)->handle($ep3->group, $this->user1->person);
        app()->make(MemberRetire::class)->handle($membership3, Carbon::today());
        Coi::factory()->create([
            'group_member_id' => $membership2->id,
            'expert_panel_id' => $ep2->id,
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
        $groupMember = app()->make(MemberAdd::class)->handle($this->ep->group, $user2->person);
        Coi::factory()->create([
            'group_member_id' => $groupMember->id,
            'expert_panel_id' => $this->ep->id,
            'completed_at' => Carbon::now()->subDays(364)
        ]);

        $ep2 = ExpertPanel::factory()->create();
        $membership2 = app()->make(MemberAdd::class)->handle($ep2->group, $this->user1->person);
        Coi::factory()->create([
            'group_member_id' => $membership2->id,
            'expert_panel_id' => $ep2->id,
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
        $ep2 = ExpertPanel::factory()->create();
        app()->make(MemberRetire::class)->handle($this->membership1, Carbon::today()->subDays(2));

        Notification::fake();
        $this->triggerScheduledTask();
        Notification::assertNothingSent();
    }
    

    private function triggerScheduledTask()
    {
        Carbon::setTestNow('2022-02-14 06:00:00');
        $this->artisan('schedule:run');
    }
}
