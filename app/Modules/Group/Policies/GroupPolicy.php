<?php

namespace App\Modules\Group\Policies;

use App\Modules\User\Models\User;
use App\Modules\Group\Models\Group;
use Illuminate\Auth\Access\HandlesAuthorization;

class GroupPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Modules\User\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Modules\User\Models\User  $user
     * @param  \App\Models\Group  $group
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Group $group)
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Modules\User\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->can('groups-manage');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Modules\User\Models\User  $user
     * @param  \App\Models\Group  $group
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Group $group)
    {
        return $user->hasGroupPermissionTo('info-edit', $group) || $user->hasPermissionTo('groups-manage');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Modules\User\Models\User  $user
     * @param  \App\Models\Group  $group
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Group $group)
    {
        return $user->hasPermissionTo('groups-manage');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Modules\User\Models\User  $user
     * @param  \App\Models\Group  $group
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Group $group)
    {
        return $user->hasPermissionTo('groups-manage');
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Modules\User\Models\User  $user
     * @param  \App\Models\Group  $group
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Group $group)
    {
        return $user->hasPermissionTo('groups-manage');
    }

    public function inviteMembers(User $user, Group $group)
    {
        return $user->hasPermissionTo('groups-manage') || $user->hasGroupPermissionTo('members-invite', $group);
    }

    public function updateMembers(User $user, Group $group)
    {
        return $user->hasPermissionTo('groups-manage') || $user->hasGroupPermissionTo('members-update', $group);
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Modules\User\Models\User  $user
     * @param  \App\Models\ExpertPanel  $expertPanel
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function updateApplicationAttribute(User $user, Group $group)
    {
        return $user->hasPermissionTo('ep-applications-manage')
            || $user->hasGroupPermissionTo('application-edit', $group);
    }

}
