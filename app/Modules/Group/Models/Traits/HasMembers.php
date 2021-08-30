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
    
    public function chairs(): Relation
    {
        return $this->members()
            ->role('chair');
    }
    
    public function coordinators(): Relation
    {
        return $this->members()
            ->role('coordinator');
    }
}
