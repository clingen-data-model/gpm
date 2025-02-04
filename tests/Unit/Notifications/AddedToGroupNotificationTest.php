<?php

namespace Tests\Unit\Notifications;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Role;
use App\Modules\Group\Models\Group;
use App\Modules\Person\Models\Invite;
use App\Modules\Person\Models\Person;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use App\Modules\Group\Notifications\AddedToGroupNotification;

class AddedToGroupNotificationTest extends TestCase
{
    use FastRefreshDatabase;
    
    public function setup():void
    {
        parent::setup();
        Role::factory()->create(['name' => 'coordinator', 'scope'=>'group']);
        $this->ep = ExpertPanel::factory()->create();
        $this->person = Person::factory()->create();
        $this->invite = Invite::factory()->create([
                            'person_id' => $this->person->id,
                            'inviter_id' => $this->ep->group_id,
                        ]);
    }

    /**
     * @test
     */
    public function includes_invite_info_if_invite_pending()
    {
        $group = Group::factory()->create();
        $this->invite->update(['redeemed_at' => null]);
        $notification = new AddedToGroupNotification($group);
        $mailBody = $notification->toMail($this->person)->render();

        $this->assertStringContainsString($this->invite->url, $mailBody);
    }

    /**
     * @test
     */
    public function does_not_include_invite_info_if_invite_redeemed()
    {
        $group = Group::factory()->create();
        $notification = new AddedToGroupNotification($group);
        $mailBody = $notification->toMail($this->person)->render();

        $this->assertStringNotContainsString($this->invite->url, $mailBody);
    }
}
