<?php

namespace Tests\Feature\End2End;

use Carbon\Carbon;
use Tests\TestCase;
use App\Modules\Person\Models\Invite;
use App\Modules\Person\Models\Person;
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
        $this->invite = Invite::factory()->create(['redeemed_at' => null]);
        config(['app.features.invite_reminders' => true]);
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
    
    
    

    private function triggerReminders()
    {
        Carbon::setTestNow('2022-02-14 06:00:00');
        $this->artisan('schedule:run');
    }
}
