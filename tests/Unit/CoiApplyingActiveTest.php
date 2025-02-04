<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Modules\Group\Models\Group;
use App\Modules\Person\Models\Person;
use App\Modules\Group\Models\GroupMember;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;

class CoiApplyingActiveTest extends TestCase
{
    use FastRefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->setupForGroupTest();
    }

    /**
     * @test
     */
    public function pending_coi_applies_to_applying_and_active_groups()
    {
        $groupMember = GroupMember::factory()->create();
        $this->assertTrue($groupMember->person->inGroup($groupMember->group));

        $group = $groupMember->group;
        $person = $groupMember->person;

        $group->group_status_id = config('groups.statuses.applying.id');
        $group->save();
        $this->assertEquals(1, $person->hasPendingCois()->count());

        $group->save();
        $group->group_status_id = config('groups.statuses.active.id');
        $this->assertEquals(1, $person->hasPendingCois()->count());
    }

    /**
     * @test
     */
    public function pending_coi_does_not_apply_to_inactive_groups()
    {
        $groupMember = GroupMember::factory()->create();
        $this->assertTrue($groupMember->person->inGroup($groupMember->group));

        $group = $groupMember->group;
        $person = $groupMember->person;

        $group->group_status_id = config('groups.statuses.inactive.id');
        $group->save();
        $this->assertEquals(0, $person->hasPendingCois()->count());
    }
}
