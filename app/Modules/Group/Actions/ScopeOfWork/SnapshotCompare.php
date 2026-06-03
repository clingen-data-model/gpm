<?php

namespace App\Modules\Group\Actions\ScopeOfWork;

use App\Modules\Group\Models\Group;
use Lorisleiva\Actions\Concerns\AsObject;

class SnapshotCompare
{
    use AsObject;

    public function handle(Group $group, array $beforeSnapshot, array $afterSnapshot): array
    {
        return collect()
            ->merge($this->comparePanelName($group, $beforeSnapshot, $afterSnapshot))
            ->merge($this->compareScopeDescription($group, $beforeSnapshot, $afterSnapshot))
            ->merge($this->compareGenes($group, $beforeSnapshot, $afterSnapshot))
            ->merge($this->compareMembers($group, $beforeSnapshot, $afterSnapshot))
            ->values()
            ->all();
    }

    private function comparePanelName(Group $group, array $beforeSnapshot, array $afterSnapshot): array
    {
        $before = data_get($beforeSnapshot, 'scope_of_work.panel_name');
        $after = data_get($afterSnapshot, 'scope_of_work.panel_name');

        if ($before === $after) {
            return [];
        }

        return [
            $this->changePayload($group, 'panel_name.rename', [
                'entity_type' => 'group',
                'entity_uuid' => data_get($afterSnapshot, 'group.uuid'),
                'entity_label' => $after,
                'field_name' => 'name',
                'before_value' => $before,
                'after_value' => $after,
            ]),
        ];
    }

    private function compareScopeDescription(Group $group, array $beforeSnapshot, array $afterSnapshot): array
    {
        $before = data_get($beforeSnapshot, 'scope_of_work.scope_description');
        $after = data_get($afterSnapshot, 'scope_of_work.scope_description');

        if ($before === $after) {
            return [];
        }

        return [
            $this->changePayload($group, 'scope_description.update', [
                'entity_type' => 'expert_panel',
                'entity_uuid' => data_get($afterSnapshot, 'expert_panel.uuid'),
                'entity_label' => data_get($afterSnapshot, 'group.name'),
                'field_name' => 'scope_description',
                'before_value' => $before,
                'after_value' => $after,
            ]),
        ];
    }

    private function compareMembers(Group $group, array $beforeSnapshot, array $afterSnapshot): array
    {
        $beforeMembers = $this->membersByKey(data_get($beforeSnapshot, 'scope_of_work.members', []));
        $afterMembers = $this->membersByKey(data_get($afterSnapshot, 'scope_of_work.members', []));

        $changes = [];

        foreach ($afterMembers as $key => $afterMember) {
            if (!isset($beforeMembers[$key])) {
                $changes[] = $this->changePayload($group, 'member.add', [
                    'entity_type' => 'member',
                    'entity_uuid' => $afterMember['person_uuid'] ?? null,
                    'entity_label' => $this->memberLabel($afterMember),
                    'before_value' => null,
                    'after_value' => $afterMember,
                ]);

                continue;
            }

            $changes = array_merge(
                $changes,
                $this->compareMemberRoles($group, $beforeMembers[$key], $afterMember)
            );
        }

        foreach ($beforeMembers as $key => $beforeMember) {
            if (!isset($afterMembers[$key])) {
                $changes[] = $this->changePayload($group, 'member.remove', [
                    'entity_type' => 'member',
                    'entity_uuid' => $beforeMember['person_uuid'] ?? null,
                    'entity_label' => $this->memberLabel($beforeMember),
                    'before_value' => $beforeMember,
                    'after_value' => null,
                ]);
            }
        }

        return $changes;
    }

    private function compareMemberRoles(Group $group, array $beforeMember, array $afterMember): array
    {
        $beforeRoles = $this->roleNames($beforeMember);
        $afterRoles = $this->roleNames($afterMember);

        if ($beforeRoles === $afterRoles) {
            return [];
        }

        $changes = [];

        $addedRoles = array_values(array_diff($afterRoles, $beforeRoles));
        $removedRoles = array_values(array_diff($beforeRoles, $afterRoles));

        foreach ($addedRoles as $role) {
            $changes[] = $this->changePayload($group, $this->roleRuleKey($role, 'add'), [
                'label' => 'Added role: ' . $this->roleLabel($role),
                'entity_type' => 'member',
                'entity_uuid' => $afterMember['person_uuid'] ?? null,
                'entity_label' => $this->memberLabel($afterMember),
                'field_name' => 'roles',
                'before_value' => null,
                'after_value' => [
                    'role' => $role,
                ],
            ]);
        }

        foreach ($removedRoles as $role) {
            $changes[] = $this->changePayload($group, $this->roleRuleKey($role, 'remove'), [
                'label' => 'Removed role: ' . $this->roleLabel($role),
                'entity_type' => 'member',
                'entity_uuid' => $afterMember['person_uuid'] ?? $beforeMember['person_uuid'] ?? null,
                'entity_label' => $this->memberLabel($afterMember ?: $beforeMember),
                'field_name' => 'roles',
                'before_value' => [
                    'role' => $role,
                ],
                'after_value' => null,
            ]);
        }

        return $changes;
    }

    private function changePayload(Group $group, string $ruleKey, array $data): array
    {
        $classification = ScopeOfWorkChangeClassify::run($group, $ruleKey);

        return array_merge([
            'rule_key' => $ruleKey,
            'area' => $classification['area'],
            'change_type' => $classification['change_type'],
            'label' => $classification['label'] ?? null,
            'impact' => $classification['impact'] ?? null,
            'requires_approval' => $classification['requires_approval'] ?? null,
            'approval_step' => $classification['approval_step'] ?? null,
            'approvers' => $classification['approvers'] ?? null,
            'condition' => $classification['condition'] ?? null,
        ], $data);
    }

    private function membersByKey(array $members): array
    {
        return collect($members)
            ->mapWithKeys(function ($member) {
                $key = $member['person_uuid']
                    ?? $member['person_id']
                    ?? $member['id'];

                return [$key => $member];
            })
            ->all();
    }

    private function roleNames(array $member): array
    {
        return collect($member['roles'] ?? [])
            ->pluck('name')
            ->filter()
            ->sort()
            ->values()
            ->all();
    }

    private function roleRuleKey(string $role, string $action): string
    {
        if ($role === config('scope_of_work.roles.chair', 'chair')) {
            return $action === 'add'
                ? 'member.add_chair'
                : 'member.remove_chair';
        }

        if (in_array($role, [
            config('scope_of_work.roles.grant_liaison', 'grant-liaison'),
            'liaison',
            'grant_liaison',
        ], true)) {
            return $action === 'add'
                ? 'member.add_liaison'
                : 'member.remove_liaison';
        }

        return 'member.update_role';
    }

    private function memberLabel(array $member): string
    {
        return trim(($member['first_name'] ?? '') . ' ' . ($member['last_name'] ?? ''));
    }

    private function roleLabel(string $role): string
    {
        return ucwords(str_replace(['-', '_'], ' ', $role));
    }

    private function compareGenes(Group $group, array $beforeSnapshot, array $afterSnapshot): array
    {
        $beforeGenes = $this->scopeGenesByKey(data_get($beforeSnapshot, 'scope_of_work.scope_genes', []));
        $afterGenes = $this->scopeGenesByKey(data_get($afterSnapshot, 'scope_of_work.scope_genes', []));

        $changes = [];

        foreach ($afterGenes as $key => $afterGene) {
            if (!isset($beforeGenes[$key])) {
                $changes[] = $this->changePayload($group, 'gene.add', [
                    'entity_type' => 'gene',
                    'entity_uuid' => $afterGene['uuid'] ?? null,
                    'entity_label' => $this->scopeGeneLabel($afterGene),
                    'before_value' => null,
                    'after_value' => $afterGene,
                ]);

                continue;
            }

            $changes = array_merge(
                $changes,
                $this->compareExistingGene($group, $beforeGenes[$key], $afterGene)
            );
        }

        foreach ($beforeGenes as $key => $beforeGene) {
            if (!isset($afterGenes[$key])) {
                $changes[] = $this->changePayload($group, 'gene.remove', [
                    'entity_type' => 'gene',
                    'entity_uuid' => $beforeGene['uuid'] ?? null,
                    'entity_label' => $this->scopeGeneLabel($beforeGene),
                    'before_value' => $beforeGene,
                    'after_value' => null,
                ]);
            }
        }

        return $changes;
    }

    private function compareExistingGene(Group $group, array $beforeGene, array $afterGene): array
    {
        $changes = [];

        if ($this->normalizeTier($beforeGene['tier'] ?? null) !== $this->normalizeTier($afterGene['tier'] ?? null)) {
            $changes[] = $this->changePayload($group, 'gene.update_tier', [
                'label' => 'Update gene tier',
                'entity_type' => 'gene',
                'entity_uuid' => $afterGene['uuid'] ?? null,
                'entity_label' => $this->scopeGeneLabel($afterGene),
                'field_name' => 'tier',
                'before_value' => [
                    'tier' => $beforeGene['tier'] ?? null,
                ],
                'after_value' => [
                    'tier' => $afterGene['tier'] ?? null,
                ],
            ]);
        }

        $fields = [
            'hgnc_id',
            'gene_symbol',
            'mondo_id',
            'disease_name',
            'disease_entity',
            'moi',
            'date_approved',
            'gt_curation_uuid',
            'plan',
        ];

        foreach ($fields as $field) {
            if (($beforeGene[$field] ?? null) === ($afterGene[$field] ?? null)) {
                continue;
            }

            $changes[] = $this->changePayload($group, 'gene.update', [
                'label' => 'Update gene: ' . $this->fieldLabel($field),
                'entity_type' => 'gene',
                'entity_uuid' => $afterGene['uuid'] ?? null,
                'entity_label' => $this->scopeGeneLabel($afterGene),
                'field_name' => $field,
                'before_value' => [
                    $field => $beforeGene[$field] ?? null,
                ],
                'after_value' => [
                    $field => $afterGene[$field] ?? null,
                ],
            ]);
        }

        return $changes;
    }

    private function scopeGenesByKey(array $genes): array
    {
        return collect($genes)->mapWithKeys(function ($gene) {
                return [$gene['id'] => $gene];
            })
            ->all();
    }

    private function scopeGeneLabel(array $gene): string
    {
        $parts = array_filter([
            $gene['gene_symbol'] ?? null,
            $gene['mondo_id'] ?? null,
            $gene['moi'] ?? null,
        ]);

        return implode(' - ', $parts);
    }

    private function normalizeTier(mixed $tier): ?string
    {
        if ($tier === '' || $tier === 'null') {
            return null;
        }

        return is_null($tier) ? null : (string) $tier;
    }

    private function fieldLabel(string $field): string
    {
        return ucwords(str_replace('_', ' ', $field));
    }
}