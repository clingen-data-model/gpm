<?php

namespace App\Modules\Group\Models\Traits;

use Illuminate\Support\Collection;
use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\GroupMember;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Is a group member
 */
trait IsGroupMember
{
    /**
     * Get all of the Members for the ExpertPanel
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function memberships(): Relation
    {
        return $this->hasMany(GroupMember::class);
    }
    
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'group_members', 'person_id', 'group_id')
                ->withPivot('start_date', 'end_date');
    }

    public function hasGroupPermissionTo(string $permission, $groups): bool
    {
        if (is_object($groups) && get_class($groups) == Group::class) {
            $groups = collect([$groups]);
        }
    
        if (is_array($groups)) {
            $groups = collect($groups);
        }
    
        $memberships = $this->memberships()
            ->isActive()
            ->whereIn('group_id', $groups->pluck('id')->toArray())
            ->with('permissions', 'roles.permissions')
            ->get();
        
        $permissions = $memberships->map(fn ($m) => $m->getAllPermissions())->flatten()->unique()->pluck("name");

        return $permissions->contains($permission);
    }

    public function isMemberOf(Group $group): bool
    {
        return $this->memberships()
                ->where('group_id', $group->id)
                ->count() > 0;
    }
}
