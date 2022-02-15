<?php

namespace Tests\Feature\End2End;

use Tests\TestCase;
use App\Actions\V2SendReleaseEmail;
use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\View;
use App\Modules\Person\Models\Invite;
use App\Modules\Person\Models\Person;
use App\Modules\Group\Actions\MemberAdd;
use App\Notifications\V2ReleaseNotification;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SendReleaseEmailTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    public function setup():void
    {
        parent::setup();
        $this->seed();
        $this->expertPanels = ExpertPanel::factory(2)->create(['long_base_name' => $this->faker->name]);
        $this->people = Person::factory(2)->create();
        
        $addMember = app()->make(MemberAdd::class);
        foreach ($this->expertPanels as $ep) {
            foreach ($this->people as $person) {
                Invite::factory()->create(['person_id' => $person->id]);
                $addMember->handle($ep->group, $person);
            }
        }
    }

    /**
     * @test
     */
    public function sends_one_v2_release_email_to_each_person()
    {
        Notification::fake();
        
        $this->artisan('v2:send-email');

        foreach ($this->people as $person) {
            Notification::assertSentToTimes($person, V2ReleaseNotification::class, 1);
        }
    }

    /**
     * @test
     */
    public function test_email_contents()
    {
        $person = $this->people->first();
        $person->load('invite', 'memberships', 'memberships.group');

        $expectedOutput = View::make('email.v2.release_notification', ['notifiable' => $person])->render();
        $renderedNotification = (new V2ReleaseNotification)->toMail($person)->render();
        $this->assertEquals($expectedOutput, $renderedNotification);

        $this->assertStringContainsString(' <a href="http://localhost/invites/'.$person->invite->code.'">http://localhost/invites/'.$person->invite->code.'</a>', $renderedNotification);
    }
}
