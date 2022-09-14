<?php

namespace Tests\Feature\End2End;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\FollowAction;
use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\GroupStatus;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\Group\Events\GroupNameUpdated;
use App\Modules\Group\Actions\GroupStatusUpdate;
use App\Modules\Group\Events\GroupStatusUpdated;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FollowActionsTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->setupForGroupTest();
        $this->group = Group::factory()->vcep()->create(['group_status_id' => 1]);
        $this->followAction = FollowAction::create([
            'event_class' => GroupStatusUpdated::class,
            'follower' => TestFollower::class,
            'args' => ['targetStatusId' => 4]
        ]);
    }

    /**
     * @test
     */
    public function runs_followAction_on_target_event()
    {
        app()->make(GroupStatusUpdate::class)->handle($this->group, GroupStatus::find(2));

        $this->assertDatabaseHas('groups', [
            'id' => $this->group->id,
            'name' => 'Updated by follow action but it\'s not complete.'
        ]);

        $this->assertDatabaseHas('follow_actions', [
            'id' => $this->followAction->id,
            'completed_at' => null
        ]);
    }
    
    /**
     * @test
     */
    public function completes_followAction_if_follower_returns_true()
    {
        Carbon::setTestNow('2022-07-15');
        app()->make(GroupStatusUpdate::class)->handle($this->group, GroupStatus::find(4));

        $this->assertDatabaseHas('groups', [
            'id' => $this->group->id,
            'name' => 'Updated by follow action'
        ]);

        $this->assertDatabaseHas('follow_actions', [
            'id' => $this->followAction->id,
            'completed_at' => Carbon::now()
        ]);
    }
    
}

class TestFollower
{
    public function asFollowAction(GroupStatusUpdated $event, $targetStatusId): bool
    {
        if ($event->group->group_status_id == $targetStatusId) {
            $event->group->update(['name' => 'Updated by follow action']);
            return true;
        }
        $event->group->update(['name' => 'Updated by follow action but it\'s not complete.']);

        return false;
    }
    
}