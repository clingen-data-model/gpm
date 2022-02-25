<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Modules\Group\Models\Group;
use App\Modules\Person\Models\Person;
use App\Modules\Group\Models\GroupMember;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PersonTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->seed();
    }
    
    /**
     * @test
     */
    public function can_determine_if_person_in_group()
    {
        $groupMember = GroupMember::factory()->create();
        $this->assertTrue($groupMember->person->inGroup($groupMember->group));

        $otherGroup = Group::factory()->create();
        $this->assertFalse($groupMember->person->inGroup($otherGroup));

        $otherMembership = GroupMember::factory()->create(['person_id' => $groupMember->person->id, 'end_date' => '2022-01-02']);
        $this->assertFalse($groupMember->person->fresh()->inGroup($otherMembership->group));
    }
}
