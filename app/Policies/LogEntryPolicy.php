<?php

namespace App\Policies;

use App\Models\Activity;
use App\Modules\User\Models\User;
use App\Modules\Group\Models\Group;
use Illuminate\Auth\Access\HandlesAuthorization;

class LogEntryPolicy
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
        return $this->managesGroupsOrApplications($user);
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Modules\User\Models\User  $user
     * @param  \App\Models\Activity  $activity
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Activity $activity)
    {
        return $this->managesGroupsOrApplications($user);
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Modules\User\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $this->managesGroupsOrApplications($user);
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Modules\User\Models\User  $user
     * @param  \App\Models\Activity $activity
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Activity $activity)
    {
        return $this->managesGroupsOrApplications($user);
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Modules\User\Models\User  $user
     * @param  \App\Models\Activity  $activity
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Activity $activity)
    {
        return $this->managesGroupsOrApplications($user);
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Modules\User\Models\User  $user
     * @param  \App\Models\Activity  $activity
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Activity $activity)
    {
        return $this->managesGroupsOrApplications($user);
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Modules\User\Models\User  $user
     * @param  \App\Models\Activity  $activity
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Activity $activity)
    {
        return $this->managesGroupsOrApplications($user);
    }

    private function managesGroupsOrApplications(User $user): bool
    {
        return $user->hasAnyPermission('groups-manage', 'ep-applications-manage');
    }

}
