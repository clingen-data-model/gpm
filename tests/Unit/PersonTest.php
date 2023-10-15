<?php

namespace Tests\Unit;

use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\GroupMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonTest extends TestCase
{
    use RefreshDatabase;

    public function setup(): void
    {
        parent::setup();
        $this->setupForGroupTest();
    }

    /**
     * @test
     */
    public function can_determine_if_person_in_group(): void
    {
        $groupMember = GroupMember::factory()->create();
        $this->assertTrue($groupMember->person->inGroup($groupMember->group));

        $otherGroup = Group::factory()->create();
        $this->assertFalse($groupMember->person->inGroup($otherGroup));

        $otherMembership = GroupMember::factory()->create(['person_id' => $groupMember->person->id, 'end_date' => '2022-01-02']);
        $this->assertFalse($groupMember->person->fresh()->inGroup($otherMembership->group));
    }
}
