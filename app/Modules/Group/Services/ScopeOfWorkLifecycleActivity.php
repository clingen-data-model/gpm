<?php

namespace App\Modules\Group\Services;

use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\ScopeOfWorkVersion;
use App\Modules\User\Models\User;

class ScopeOfWorkLifecycleActivity
{
    public static function created(
        Group $group,
        ScopeOfWorkVersion $draft,
        ScopeOfWorkVersion $approved,
        ?User $user
    ): void {
        activity('groups')
            ->performedOn($group)
            ->causedBy($user)
            ->withProperties([
                'draft_version_id' => $draft->id,
                'approved_version_id' => $approved->id,
            ])
            ->log("Scope of Work draft {$draft->version_label} created from approved version {$approved->version_label}.");
    }

    public static function discarded(
        Group $group,
        ScopeOfWorkVersion $draft,
        ScopeOfWorkVersion $approved,
        ?User $user
    ): void {
        activity('groups')
            ->performedOn($group)
            ->causedBy($user)
            ->withProperties([
                'draft_version_id' => $draft->id,
                'approved_version_id' => $approved->id,
            ])
            ->log("Scope of Work draft {$draft->version_label} discarded. Restored approved version {$approved->version_label}.");
    }
}
