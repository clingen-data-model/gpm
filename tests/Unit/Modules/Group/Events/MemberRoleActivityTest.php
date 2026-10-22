<?php

namespace Tests\Unit\Modules\Group\Events;

use App\Models\Role;
use App\Modules\Group\Events\MemberRoleAssigned;
use App\Modules\Group\Events\MemberRoleRemoved;
use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\GroupMember;
use App\Modules\Person\Models\Person;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class MemberRoleActivityTest extends TestCase
{
    public function test_assignment_log_uses_person_name_and_human_readable_single_role(): void
    {
        $event = new MemberRoleAssigned(
            $this->groupMember(),
            new Collection([new Role(['name' => 'grant-liaison', 'display_name' => 'Grant Liaison'])])
        );

        $this->assertSame('John Smith given role Grant Liaison', $event->getLogEntry());
    }

    public function test_assignment_log_uses_person_name_and_human_readable_multiple_roles(): void
    {
        $event = new MemberRoleAssigned(
            $this->groupMember(),
            new Collection([
                new Role(['name' => 'chair', 'display_name' => 'Chair']),
                new Role(['name' => 'grant-liaison', 'display_name' => 'Grant Liaison']),
            ])
        );

        $this->assertSame('John Smith given roles Chair and Grant Liaison', $event->getLogEntry());
    }

    public function test_removal_log_uses_human_readable_role_and_person_name(): void
    {
        $event = new MemberRoleRemoved(
            $this->groupMember(),
            new Role(['name' => 'grant-liaison', 'display_name' => 'Grant Liaison'])
        );

        $this->assertSame('Removed role Grant Liaison from member John Smith.', $event->getLogEntry());
    }

    private function groupMember(): GroupMember
    {
        $member = new GroupMember();
        $member->setRelation('group', new Group());

        $person = new Person(['first_name' => 'John', 'last_name' => 'Smith']);
        $member->setRelation('person', $person);

        return $member;
    }
}
