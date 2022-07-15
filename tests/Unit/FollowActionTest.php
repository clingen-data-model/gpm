<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\FollowAction;
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
            'follower' => new TestFollower('beans')
        ]);
        $fa->save();

        $fa1 = FollowAction::find($fa->id);

        $event = new TestEvent('beans');

        $follower = $fa1->follower;

        $this->assertTrue($follower($event));
    }
    
}

class TestEvent
{
    public function __construct(public $argument)
    {
    }
}

class TestFollower
{
    public function __construct(private $name)
    {
        //code
    }

    public function __invoke(TestEvent $event): bool
    {
        if ($event->argument == $this->name) {
            return true;
        }

        return false;
    }
    
    
}