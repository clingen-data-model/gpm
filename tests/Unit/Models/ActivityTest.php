<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Activity;

class ActivityTest extends TestCase
{
    /**
     * @test
     */
    public function has_fillable_activity_type_attribute()
    {
        $activity = new Activity();
        $activity->fill(['activity_type'=>'test_type']);

        $this->assertEquals('test_type', $activity->activity_type);
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
