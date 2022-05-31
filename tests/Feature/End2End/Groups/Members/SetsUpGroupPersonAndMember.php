<?php
namespace Tests\Feature\End2End\Groups\Members;

use App\Modules\User\Models\User;
use App\Modules\Group\Models\Group;
use App\Modules\Person\Models\Person;
use App\Modules\Group\Actions\MemberAdd;

/**
 * 
 */
trait SetsUpGroupPersonAndMember
{
    public function setupEntities()
    {
        $this->group = Group::factory()->create();
        $this->person = Person::factory()->create();
        $this->user = User::factory()->create();

        return $this;
    }

    public function setupMember(?Group $group = null, ?Person $person = null, $memberAttributes = [])
    {
        $group = $group ?? $this->group;
        $person = $person ?? $this->person;
        $this->groupMember = app()->make(MemberAdd::class)->handle($group, $person, $memberAttributes);
        return $this;
    }
    
}
