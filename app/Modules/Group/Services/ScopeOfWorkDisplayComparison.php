<?php

namespace App\Modules\Group\Services;

use Illuminate\Support\Arr;

/** Display-only comparison of stored JSON. No models or approval rules are consulted. */
class ScopeOfWorkDisplayComparison
{
    public function handle(?array $before, ?array $after): array
    {
        $before = $this->normalize($before ?? []);
        $after = $this->normalize($after ?? []);
        $rows = ['genes' => null, 'members' => null];
        $changes = [];
        $unavailable = [];
        foreach (['group.name', 'group.description', 'expert_panel.long_base_name', 'expert_panel.short_base_name',
            'scope_description', 'membership_description', 'genes', 'members'] as $section) {
            if (!array_key_exists($section, $before) || !array_key_exists($section, $after)) {
                $unavailable[] = $section;
                continue;
            }
            $old = $before[$section];
            $new = $after[$section];
            if (in_array($section, ['genes', 'members'], true)) {
                $old = $this->index($old, $section === 'genes' ? 'id' : 'person_id');
                $new = $this->index($new, $section === 'genes' ? 'id' : 'person_id');
                if ($old === null || $new === null) {
                    $unavailable[] = $section;
                    continue;
                }
                $rows[$section] = $this->combinedRows($old, $new, $section);
                foreach (array_diff_key($old, $new) as $key => $item) {
                    $changes[] = $this->change($section, (string) $key, 'removed', $item, null);
                }
                foreach (array_diff_key($new, $old) as $key => $item) {
                    $changes[] = $this->change($section, (string) $key, 'added', null, $item);
                }
                if ($section === 'members') {
                    foreach ([$old, $new] as $members) {
                        foreach ($members as $key => $member) {
                            if ($this->index($member['roles'] ?? null, 'name') === null) {
                                $unavailable[] = 'member_roles:'.$key;
                            }
                        }
                    }
                    foreach (array_intersect_key($new, $old) as $key => $member) {
                        $oldRoles = $this->index($old[$key]['roles'] ?? null, 'name');
                        $newRoles = $this->index($member['roles'] ?? null, 'name');
                        if ($oldRoles === null || $newRoles === null) {
                            continue;
                        }
                        foreach (array_diff_key($oldRoles, $newRoles) as $role => $value) {
                            $changes[] = $this->change('member_roles', $key.':'.$role, 'removed', $value, null, $member['label']);
                        }
                        foreach (array_diff_key($newRoles, $oldRoles) as $role => $value) {
                            $changes[] = $this->change('member_roles', $key.':'.$role, 'added', null, $value, $member['label']);
                        }
                    }
                }
            } elseif ($old !== $new) {
                $changes[] = $this->change($section, $section, 'modified', $old, $new);
            }
        }
        return [
            'status' => $unavailable ? 'partial' : 'complete',
            'unavailable_sections' => array_values(array_unique($unavailable)),
            'summary' => ['changed_items' => count($changes)],
            'changes' => $changes,
            'rows' => $rows,
        ];
    }

    private function combinedRows(array $before, array $after, string $section): array
    {
        $rows = [];
        foreach (array_unique(array_merge(array_keys($before), array_keys($after))) as $key) {
            $old = $before[$key] ?? null;
            $new = $after[$key] ?? null;
            $operation = $old === null ? 'added' : ($new === null ? 'removed' : 'unchanged');
            $roles = null;
            if ($section === 'members') {
                $oldRoles = $old === null ? [] : $this->index($old['roles'] ?? null, 'name');
                $newRoles = $new === null ? [] : $this->index($new['roles'] ?? null, 'name');
                if ($oldRoles !== null && $newRoles !== null) {
                    $roles = $this->combinedRows($oldRoles, $newRoles, 'roles');
                }
                if ($old !== null && $new !== null
                    && ($old['label'] !== $new['label']
                        || collect($roles ?? [])->contains(fn ($role) => $role['operation'] !== 'unchanged'))) {
                    $operation = 'changed';
                }
            } elseif ($section !== 'roles' && $old !== null && $new !== null && $old !== $new) {
                $operation = 'changed';
            }
            $row = $this->change($section, (string) $key, $operation, $old, $new);
            if ($section === 'members') {
                $row['roles'] = $roles;
                $row['roles_available'] = $roles !== null;
            }
            $rows[] = $row;
        }
        return $rows;
    }

    private function normalize(array $snapshot): array
    {
        $application = array_key_exists('attributes', $snapshot);
        $paths = $application ? [
            'group.name' => 'attributes.name', 'group.description' => 'attributes.description',
            'expert_panel.long_base_name' => 'relations.expertPanel.attributes.long_base_name',
            'expert_panel.short_base_name' => 'relations.expertPanel.attributes.short_base_name',
            'scope_description' => 'relations.expertPanel.attributes.scope_description',
            'membership_description' => 'relations.expertPanel.attributes.membership_description',
            'genes' => 'relations.expertPanel.relations.genes', 'members' => 'relations.members',
        ] : [
            'group.name' => 'group.name', 'group.description' => 'group.description',
            'expert_panel.long_base_name' => 'expert_panel.long_base_name',
            'expert_panel.short_base_name' => 'expert_panel.short_base_name',
            'scope_description' => 'scope_of_work.scope_description',
            'membership_description' => 'scope_of_work.membership_description',
            'genes' => 'scope_of_work.scope_genes', 'members' => 'scope_of_work.members',
        ];
        $result = [];
        foreach ($paths as $key => $path) {
            if (!Arr::has($snapshot, $path)) {
                continue;
            }
            $value = data_get($snapshot, $path);
            if (!in_array($key, ['genes', 'members'], true)) {
                if (is_scalar($value) || $value === null) {
                    $result[$key] = $value === null ? null : (string) $value;
                }
                continue;
            }
            if (!is_array($value) || !array_is_list($value)) {
                continue;
            }
            $items = [];
            foreach ($value as $entry) {
                $item = $application ? ($entry['attributes'] ?? null) : $entry;
                if (!is_array($item)) {
                    continue 2;
                }
                if ($key === 'genes') {
                    $items[] = [
                        'id' => $item['id'] ?? null,
                        'label' => implode(' ? ', array_filter([$item['gene_symbol'] ?? null, $item['mondo_id'] ?? null, $item['moi'] ?? null])),
                        'gene_symbol' => $item['gene_symbol'] ?? null,
                        'hgnc_id' => $item['hgnc_id'] ?? null,
                    ];
                } else {
                    $person = $application ? data_get($entry, 'relations.person.attributes', []) : $item;
                    $member = [
                        'person_id' => $item['person_id'] ?? null,
                        'label' => trim(($person['first_name'] ?? '').' '.($person['last_name'] ?? '')),
                    ];
                    $roles = $application ? data_get($entry, 'relations.roles') : ($item['roles'] ?? null);
                    if (is_array($roles) && array_is_list($roles)) {
                        $member['roles'] = array_map(function ($role) use ($application) {
                            $attributes = $application ? ($role['attributes'] ?? []) : $role;
                            return [
                                'name' => $attributes['name'] ?? null,
                                'label' => $attributes['display_name'] ?? $attributes['name'] ?? null,
                            ];
                        }, $roles);
                    }
                    $items[] = $member;
                }
            }
            $result[$key] = $items;
        }
        return $result;
    }

    private function index($items, string $key): ?array
    {
        if (!is_array($items)) {
            return null;
        }
        $indexed = [];
        foreach ($items as $item) {
            $id = $item[$key] ?? null;
            if ((!is_string($id) && !is_int($id)) || $id === '' || isset($indexed[$id])) {
                return null;
            }
            $indexed[$id] = $item;
        }
        return $indexed;
    }

    private function change(string $section, string $key, string $operation, $before, $after, ?string $label = null): array
    {
        return [
            'section' => $section, 'key' => $key, 'operation' => $operation,
            'label' => $label ?? (is_array($after ?? $before) ? (($after ?? $before)['label'] ?? $key) : $key),
            'before' => $before, 'after' => $after,
        ];
    }
}
