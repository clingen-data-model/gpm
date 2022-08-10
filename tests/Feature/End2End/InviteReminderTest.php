<?php

namespace Tests\Feature\End2End;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Role;
use App\Modules\Group\Models\Group;
use App\Modules\Person\Models\Invite;
use App\Modules\Person\Models\Person;
use App\Modules\Group\Actions\MemberAdd;
use App\Modules\Group\Actions\MemberRetire;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use App\Notifications\InviteReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InviteReminderTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        Role::factory()->create(['name' => 'coordinator', 'scope' => 'group']);
        $this->invite = Invite::factory()->create(['redeemed_at' => null]);
        $this->addMember = app()->make(MemberAdd::class);
        $this->group1 = Group::factory()->create();
        $this->gm1 = $this->addMember->handle($this->group1, $this->invite->person);
    }

    /**
     * @test
     */
    public function person_can_scope_to_hasActiveMembership()
    {
        $this->assertEquals(1, Person::hasActiveMembership()->count());

        app()->make(MemberRetire::class)->handle($this->gm1, Carbon::now());

        $this->assertEquals(0, person::hasActiveMembership()->count());
    }


    /**
     * @test
     */
    public function it_sends_an_email_to_each_person_that_has_not_redeemed_their_invite()
    {
        Notification::fake();
        $this->triggerReminders();
        Notification::assertSentTo($this->invite->person, InviteReminderNotification::class);
    }

    /**
     * @test
     */
    public function it_does_not_send_a_reminder_to_a_person_that_has_already_activated_their_account()
    {
        $user = $this->setupUserWithPerson();
        $invite = Invite::factory()->create(['person_id' => $user->person->id, 'redeemed_at' => Carbon::now()]);
        app()->make(MemberAdd::class)->handle($this->group1, $user->person);

        $this->assertContains($user->person->id, Person::hasActiveMembership()->get()->pluck('id'));
        $this->assertNotNull($user->person->invite);

        Notification::fake();
        $this->triggerReminders();
        Notification::assertNotSentTo($user->person, InviteReminderNotification::class);
    }

    /**
     * @test
     */
    public function the_email_body_has_the_correct_content()
    {
        $notification = new InviteReminderNotification($this->invite);
        $mailable = $notification->toMail($this->invite->person);

        $emailBody = $mailable->render();

        $this->assertStringContainsString($this->invite->inviter->display_name, $emailBody);
        $this->assertStringContainsString('/invites/'.$this->invite->code, $emailBody);
    }

    /**
     * @test
     */
    public function it_does_not_email_inactive_group_members()
    {
        $retireMember = app()->make(MemberRetire::class);

        $group2 = Group::factory()->create();
        $gm2 = $this->addMember->handle($group2, $this->invite->person);

        $retireMember->handle($this->gm1, Carbon::now());
        Notification::fake();

        $this->triggerReminders();
        Notification::assertSentTo($this->invite->person->refresh(), InviteReminderNotification::class);

        Notification::fake();
        $retireMember->handle($gm2, Carbon::now());
        Notification::assertNotSentTo($this->invite->person, InviteReminderNotification::class);
    }





    private function triggerReminders()
    {
        Carbon::setTestNow('2022-02-14 06:00:00');
        $this->artisan('schedule:run');
    }
}
