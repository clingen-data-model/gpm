<?php

namespace App\Modules\Group\Services;

use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\ScopeOfWorkChange;
use App\Modules\User\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class ScopeOfWorkChangeRestorer
{
    public static function canDiscard(?User $user, Group $group, ScopeOfWorkChange $change): bool
    {
        return match ($change->rule_key) {
            'panel_name.rename' => $user?->hasPermissionTo('groups-manage') ?? false,
            'scope_description.update' => $user?->can('updateApplicationAttribute', $group) ?? false,
            default => false,
        };
    }

    public function restore(Group $group, ScopeOfWorkChange $change, array $baseline, array $recorded, array $current): void
    {
        $paths = match ($change->rule_key) {
            'panel_name.rename' => ['group.name', 'expert_panel.long_base_name', 'expert_panel.short_base_name'],
            'scope_description.update' => ['scope_of_work.scope_description'],
            default => throw ValidationException::withMessages(['change' => 'This change cannot yet be discarded individually.']),
        };

        foreach ($paths as $path) {
            if (!Arr::has($baseline, $path)) {
                throw ValidationException::withMessages(['baseline' => "The approved snapshot is missing {$path}."]);
            }
            abort_unless(Arr::has($recorded, $path) && Arr::has($current, $path)
                && data_get($recorded, $path) === data_get($current, $path), 409,
                'The revision changed. Refresh and retry.');
        }

        if ($change->rule_key === 'panel_name.rename') {
            $group->update(['name' => data_get($baseline, 'group.name')]);
            $group->expertPanel->update([
                'long_base_name' => data_get($baseline, 'expert_panel.long_base_name'),
                'short_base_name' => data_get($baseline, 'expert_panel.short_base_name'),
            ]);
        } else {
            $group->expertPanel->update(['scope_description' => data_get($baseline, 'scope_of_work.scope_description')]);
        }
    }
}
