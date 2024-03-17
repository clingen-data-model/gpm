<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Activity;

class ActivityTest extends TestCase
{
    /**
     * @test
     */
    public function has_fillable_activity_type_attribute(): void
    {
        $activity = new Activity();
        $activity->fill(['activity_type'=>'test_type']);

        $this->assertEquals('test_type', $activity->activity_type);
    }

    /**
     * @test
     */
    public function has_fillable_event_uuid_attribute(): void
    {
        $activity = new Activity();
        $activity->fill(['event_uuid'=>'test_uuid']);

        $this->assertEquals('test_uuid', $activity->event_uuid);
    }

    /**
     * @test
     */
    public function has_type_accessor_and_mutator()
    {
        $activity = new Activity(['activity_type' => 'test']);

        $this->assertEquals('test', $activity->type);
        
        $activity->type = 'bird_cat';
        
        $this->assertEquals('bird_cat', $activity->activity_type);
    }
}
