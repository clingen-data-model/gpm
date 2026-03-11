<?php

namespace App\Modules\Group\Models\Traits;

use App\Modules\Person\Models\Person;
use App\Modules\Group\Models\GroupMember;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Handles default hasMembers relation functionality
 */
trait HasMembers
{
    /**
     * Get all of the Members for the ExpertPanel
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function members(): Relation
    {
        return $this->hasMany(GroupMember::class);
    }

    public function activeMembers(): Relation
    {
        return $this->members()->isActive();
    }

    protected function activeMembersByRole(string $role): Relation
    {
        return $this->activeMembers()->role($role);
    }
    
    public function chairs(): Relation
    {
        return $this->activeMembersByRole(config('groups.roles.chair.name'));

    }
    
    public function coordinators(): Relation
    {
        return $this->activeMembersByRole(config('groups.roles.coordinator.name'));
    }

    public function contacts(): Relation
    {
        return $this->activeMembers()->isContact();
    }
    
    public function getHasCoordinatorAttribute():bool
    {
        return $this->coordinators->count() > 0;
    }
}
