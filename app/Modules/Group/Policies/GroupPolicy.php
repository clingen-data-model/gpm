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
     * @param  \App\Modules\Group\Models\Group  $group
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
     * @param  \App\Modules\Group\Models\Group  $group
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
     * @param  \App\Modules\Group\Models\Group  $group
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
     * @param  \App\Modules\Group\Models\Group  $group
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
     * @param  \App\Modules\Group\Models\Group  $group
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Group $group)
    {
        return $user->hasPermissionTo('groups-manage');
    }

    public function inviteMembers(User $user, Group $group)
    {
        return $user->hasAnyPermission(['groups-manage', 'ep-applications-manage', 'annual-updates-manage'])
            || $user->hasGroupPermissionTo('members-invite', $group);
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

    /**
     * Determine whether the user can add a gene to an EPs scope.
     *
     * @param  \App\Modules\User\Models\User  $user
     * @param  \App\Models\ExpertPanel  $expertPanel
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function addGene(User $user, Group $group)
    {
        return $user->hasPermissionTo('ep-applications-manage')
            || $user->hasGroupPermissionTo('application-edit', $group);
    }

    /**
     * Determine whether the user can update a gene in the EPs scope.
     *
     * @param  \App\Modules\User\Models\User  $user
     * @param  \App\Models\ExpertPanel  $expertPanel
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function updateGene(User $user, Group $group)
    {
        return $user->hasPermissionTo('ep-applications-manage')
            || $user->hasGroupPermissionTo('application-edit', $group);
    }

    /**
     * Determine whether the user can remove a gene to an EPs scope.
     *
     * @param  \App\Modules\User\Models\User  $user
     * @param  \App\Models\ExpertPanel  $expertPanel
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function removeGene(User $user, Group $group)
    {
        return $user->hasPermissionTo('ep-applications-manage')
            || $user->hasGroupPermissionTo('application-edit', $group);
    }

    /**
     * Determine whether the user can add an evidence summary to a VCEP.
     *
     * @param  \App\Modules\User\Models\User  $user
     * @param  \App\Models\ExpertPanel  $expertPanel
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function addEvidenceSummary(User $user, Group $group)
    {
        return $user->hasPermissionTo('ep-applications-manage')
            || $user->hasGroupPermissionTo('application-edit', $group);
    }

    public function updateEvidenceSummary(User $user, Group $group)
    {
        return $user->hasPermissionTo('ep-applications-manage')
            || $user->hasGroupPermissionTo('application-edit', $group);
    }

    public function updateCurationReviewProtocol(User $user, Group $group)
    {
        return $user->hasPermissionTo('ep-applications-manage')
            || $user->hasGroupPermissionTo('application-edit', $group);
    }

    public function makeAttestation(User $user, Group $group)
    {
        return $user->hasPermissionTo('ep-applications-manage')
            || $user->hasGroupPermissionTo('application-edit', $group);
    }

    public function manageGroupDocuments(User $user, Group $group): bool
    {
        return $user->hasAnyPermission('groups-manage', 'ep-applications-manage')
            || ($user->person && $user->person->isMemberOf($group));
    }

    public function manageAnnualUpdate(User $user, Group $group): bool
    {
        return $user->hasAnyPermission('annual-updates-manage')
            || $user->hasGroupPermissionTo('application-edit', $group);
    }

    public function retireMember(User $user, Group $group): bool
    {
        return $user->hasAnyPermission(['groups-manage', 'ep-applications-manage', 'annual-updates-manage'])
            || $user->hasGroupPermissionTo('members-retire', $group);
    }

    public function viewGroupLogs(User $user, Group $group)
    {
        return $this->managesGroupsOrApplications($user)
            || ($user->person && $user->person->isMemberOf($group));
    }


    private function managesGroupsOrApplications(User $user): bool
    {
        return $user->hasAnyPermission('groups-manage', 'ep-applications-manage');
    }

    public function checkpoint(User $user, ?Group $group = null): bool
    {        
        if ($user->hasAnyRole(['super-user', 'super-admin'])) { return true; }

        if (is_null($group)) { return false; }

        $personId = $user->person_id ?? null;
        if (!$personId) { return false; }

        return $group->members() // relationship to GroupMember
            ->where('person_id', $personId)
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['coordinator']))
            ->exists();
    }

}
