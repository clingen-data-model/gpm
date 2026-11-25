<?php

namespace App\Modules\Group\Policies;

use App\Modules\Group\Models\{Group, ExpertPanelInactivationRequest};
use App\Modules\User\Models\User;

class ExpertPanelInactivationRequestPolicy
{
    public function viewGroup(User $user, Group $group): bool
    {
        return ExpertPanelInactivationRequest::eligible($group) && (
            $user->hasAnyRole(['super-user', 'super-admin'])
            || $user->hasAnyPermission(['groups-manage', 'ep-applications-manage', 'ep-applications-comment', 'ep-applications-approve'])
            || ($user->person && $group->memberIsActive($user->person->id))
        );
    }

    public function create(User $user, Group $group): bool
    {
        return ExpertPanelInactivationRequest::eligible($group) && (
            $user->hasAnyRole(['super-user', 'super-admin'])
            || ($user->person && $group->memberIsCoordinator($user->person->id))
        );
    }

    public function view(User $user, ExpertPanelInactivationRequest $request): bool
    {
        return $this->viewGroup($user, $request->group);
    }

    public function update(User $user, ExpertPanelInactivationRequest $request): bool
    {
        // Revision editing is enabled with the Phase 2 review actions.
        return $request->status === ExpertPanelInactivationRequest::DRAFT && $this->create($user, $request->group);
    }

    public function delete(User $user, ExpertPanelInactivationRequest $request): bool
    {
        return $request->status === ExpertPanelInactivationRequest::DRAFT && $this->create($user, $request->group);
    }
}
