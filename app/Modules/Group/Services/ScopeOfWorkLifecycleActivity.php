<?php

namespace App\Modules\Group\Services;

use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\ScopeOfWorkChange;
use App\Modules\Group\Models\ScopeOfWorkVersion;
use App\Modules\User\Models\User;

class ScopeOfWorkLifecycleActivity
{
    public static function changeDiscarded(
        Group $group,
        ScopeOfWorkVersion $draft,
        ScopeOfWorkChange $change,
        User $user
    ): void {
        $description = match ($change->rule_key) {
            'panel_name.rename' => 'EP name change',
            'scope_description.update' => 'scope description change',
            'gene.add', 'gene.remove', 'gene.update', 'gene.update_tier' => 'gene change',
            'member.update_role', 'member.add_chair', 'member.remove_chair' => 'member role change',
            'member.add' => 'member addition',
            'member.remove' => 'member removal',
            'member.retire' => 'member retirement',
            'member.unretire' => 'member unretirement',
            default => 'change',
        };
        if (str_starts_with($change->rule_key, 'gene.') || str_starts_with($change->rule_key, 'member.')) {
            $label = trim($change->entity_label ?? '');
            if ($label !== '') {
                $description .= " for {$label}";
            }
        }

        activity('groups')->performedOn($group)->causedBy($user)
            ->withProperties([
                'draft_version_id' => $draft->id,
                'change_id' => $change->id,
                'rule_key' => $change->rule_key,
            ])
            ->log("Scope of Work draft {$draft->version_label}: {$description} discarded.");
    }

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
