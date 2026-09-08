<?php

namespace App\Modules\Group\Services;

use App\Modules\Group\Events\GroupCheckpointEvent;
use App\Modules\Group\Events\GroupEvent;
use App\Modules\Group\Events\GroupNameUpdated;
use App\Modules\Group\Models\GroupMember;

class ScopeOfWorkDxPayloadProjector
{
    public function __construct(private ScopeOfWorkPayloadSourceResolver $sourceResolver)
    {
    }

    public function project(GroupEvent $event, array $message): array
    {
        $snapshot = $this->sourceResolver->resolve($event->group);
        if ($snapshot === null) {
            return $message;
        }

        $scope = $snapshot['scope_of_work'] ?? [];
        $ep = $snapshot['expert_panel'] ?? [];
        // Missing keys mean uncaptured data; explicit null/empty values are approved state.
        if (! array_key_exists('panel_name', $scope) && array_key_exists('name', $snapshot['group'] ?? [])) {
            $scope['panel_name'] = $snapshot['group']['name'];
        }
        foreach (['scope_description', 'membership_description'] as $field) {
            if (! array_key_exists($field, $ep) && array_key_exists($field, $scope)) {
                $ep[$field] = $scope[$field];
            }
        }
        $genes = collect($scope['scope_genes'] ?? [])
            ->filter(fn ($gene) => empty($gene['deleted_at']))
            ->map(fn ($gene) => $event->mapGeneForMessage((object) $gene))->values()->all();
        $members = collect($scope['members'] ?? [])
            ->filter(fn ($member) => empty($member['deleted_at']) && empty($member['end_date']))
            ->map(fn ($member) => $this->member($event, $member))->values()->all();

        if ($event instanceof GroupCheckpointEvent) {
            return $this->group($message, $scope, $ep, $genes, $members);
        }
        if (isset($message['group'])) {
            $message['group'] = $this->group($message['group'], $scope, $ep, $genes, $members);
        }
        if (array_key_exists('scope_genes', $scope) && array_key_exists('genes', $message)) {
            $affected = isset($event->genes) ? $event->genes->pluck('id')->all() : [$event->gene->id];
            $message['genes'] = collect($scope['scope_genes'] ?? [])
                ->whereIn('id', $affected)
                ->map(fn ($gene) => $event->mapGeneForMessage((object) $gene))->values()->all();
        }
        if (array_key_exists('members', $scope) && array_key_exists('members', $message)) {
            $message['members'] = $members;
        }
        if (isset($message['scope'])) {
            if (array_key_exists('scope_description', $ep)) {
                $message['scope']['statement'] = $ep['scope_description'];
            }
            if (array_key_exists('scope_genes', $scope)) {
                $message['scope']['genes'] = $genes;
            }
        }
        if ($event instanceof GroupNameUpdated && array_key_exists('panel_name', $scope)) {
            $message['new_name'] = $message['old_name'] = $scope['panel_name'] ?? data_get($snapshot, 'group.name');
        }
        foreach (['long_base_name', 'short_base_name', 'scope_description', 'membership_description'] as $field) {
            foreach ([$field, 'old_'.$field, 'new_'.$field] as $key) {
                if (array_key_exists($key, $message) && array_key_exists($field, $ep)) {
                    $message[$key] = $ep[$field];
                }
            }
        }
        if ($event instanceof \App\Modules\ExpertPanel\Events\ExpertPanelAttributesUpdated && array_key_exists('name', $message) && array_key_exists('panel_name', $scope)) {
            $message['name'] = $scope['panel_name'] ?? data_get($snapshot, 'group.name');
        }
        if (isset($event->groupMember) && array_key_exists('members', $scope)) {
            $member = collect($scope['members'] ?? [])->firstWhere('id', $event->groupMember->id);
            if (array_key_exists('members', $message)) {
                $message['members'] = $member ? [$this->member($event, $member)] : [];
            }
            if (array_key_exists('roles', $message) && (! $member || array_key_exists('roles', $member))) {
                $message['roles'] = array_column($member['roles'] ?? [], 'display_name');
            }
            if (array_key_exists('end_date', $message) && (! $member || array_key_exists('end_date', $member))) {
                $message['end_date'] = $member['end_date'] ?? null;
            }
            if (isset($message['new_data'])) {
                foreach ($message['new_data'] as $key => $value) {
                    if (array_key_exists($key, $member ?? [])) {
                        $message['new_data'][$key] = $member[$key];
                    }
                }
                if (! $member) {
                    $message['new_data'] = [];
                    $message['group_member'] = null;
                }
            }
        }

        return $message;
    }

    private function group(array $data, array $scope, array $ep, array $genes, array $members): array
    {
        if (array_key_exists('panel_name', $scope)) {
            $data['name'] = $scope['panel_name'];
        }
        if (array_key_exists('members', $scope) && array_key_exists('members', $data)) {
            $data['members'] = $members;
        }
        if (isset($data['expert_panel'])) {
            foreach (['name' => 'long_base_name', 'short_name' => 'short_base_name',
                'scope_description' => 'scope_description', 'membership_description' => 'membership_description'] as $key => $field) {
                if (array_key_exists($field, $ep)) {
                    $data['expert_panel'][$key] = $ep[$field];
                }
            }
            if (array_key_exists('scope_genes', $scope) && array_key_exists('all_genes', $data['expert_panel'])) {
                $data['expert_panel']['all_genes'] = $genes;
            }
        }
        return $data;
    }

    private function member(GroupEvent $event, array $snapshot): array
    {
        // Keep non-SoW profile/permission data current, even for a member removed in the draft.
        $live = GroupMember::withTrashed()->where('group_id', $event->group->id)
            ->with(['person.latestCocAttestation', 'person.credentials', 'person.institution', 'roles', 'permissions'])
            ->find($snapshot['id']);
        $data = $live && $live->person ? $event->mapMemberForMessage($live) : [
            'uuid' => $snapshot['person_uuid'],
            'first_name' => $snapshot['first_name'], 'last_name' => $snapshot['last_name'],
            'institution' => $snapshot['institution'], 'credentials' => $snapshot['credentials'],
            'additional_permissions' => [], 'code_of_conduct' => null,
        ];
        if (! array_key_exists('roles', $snapshot)) {
            return $data;
        }
        $data['roles'] = array_column($snapshot['roles'], 'display_name');
        unset($data['email']);
        if (array_intersect($data['roles'], ['Coordinator', 'Chair'])) {
            $data['email'] = $live?->person?->email ?? $snapshot['email'] ?? null;
        }
        return $data;
    }
}
