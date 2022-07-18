<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\FollowAction;
use Tests\Dummies\TestEvent;
use Tests\Dummies\TestFollower;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FollowActionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function can_exec_a_follower()
    {
        $fa = new FollowAction([
            'event_class' => TestEvent::class,
            'follower' => TestFollower::class,
            'args' => ['name' => 'beans']
        ]);
        $fa->save();

        $fa1 = FollowAction::find($fa->id);

        $event = new TestEvent('beans');

        $follower = app()->make($fa1->follower);

        $this->assertTrue($follower->asFollowAction($event, 'beans'));
    }
    
}