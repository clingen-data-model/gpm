<?php

namespace App\Modules\Group\Services;

use Illuminate\Support\Arr;

/** Display-only comparison of stored JSON. No models or approval rules are consulted. */
class ScopeOfWorkDisplayComparison
{
    private const GENE_FIELDS = ['gene_symbol', 'hgnc_id', 'mondo_id', 'disease_name',
        'disease_entity', 'moi', 'tier', 'plan', 'date_approved', 'gt_curation_uuid'];

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
                if ($section === 'members') {
                    foreach ($rows[$section] as $row) {
                        if ($row['field_changes']) {
                            $changes[] = $this->change('members', $row['key'], 'modified', $row['before'], $row['after']);
                        }
                    }
                }
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
            } elseif ($section !== 'roles' && $section !== 'genes' && $old !== null && $new !== null && $old !== $new) {
                $operation = 'changed';
            }
            $fieldChanges = [];
            $unavailableFields = [];
            if ($section === 'members') {
                if (($old !== null && !array_key_exists('end_date', $old))
                    || ($new !== null && !array_key_exists('end_date', $new))) {
                    $unavailableFields[] = 'end_date';
                } elseif ($old !== null && $new !== null && $old['end_date'] !== $new['end_date']) {
                    $fieldChanges[] = ['field' => 'end_date', 'before' => $old['end_date'], 'after' => $new['end_date']];
                    $operation = 'changed';
                }
            }
            if ($section === 'genes') {
                foreach (self::GENE_FIELDS as $field) {
                    if (($old !== null && !array_key_exists($field, $old))
                        || ($new !== null && !array_key_exists($field, $new))) {
                        $unavailableFields[] = $field;
                    } elseif ($old !== null && $new !== null && $old[$field] !== $new[$field]) {
                        $fieldChanges[] = ['field' => $field, 'before' => $old[$field], 'after' => $new[$field]];
                    }
                }
                if ($old !== null && $new !== null && $fieldChanges) {
                    $operation = 'changed';
                }
            }
            $row = $this->change($section, (string) $key, $operation, $old, $new);
            if ($section === 'genes') {
                $row['field_changes'] = $fieldChanges;
                $row['unavailable_fields'] = $unavailableFields;
            }
            if ($section === 'members') {
                $row['field_changes'] = $fieldChanges;
                $row['unavailable_fields'] = $unavailableFields;
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
                    $gene = [
                        'id' => $item['id'] ?? null,
                        'label' => implode(' ? ', array_filter([$item['gene_symbol'] ?? null, $item['mondo_id'] ?? null, $item['moi'] ?? null])),
                    ];
                    foreach (self::GENE_FIELDS as $field) {
                        if (array_key_exists($field, $item)) {
                            $gene[$field] = $this->normalizeGeneValue($field, $item[$field]);
                        }
                    }
                    $items[] = $gene;
                } else {
                    $person = $application ? data_get($entry, 'relations.person.attributes', []) : $item;
                    $member = [
                        'person_id' => $item['person_id'] ?? null,
                        'label' => trim(($person['first_name'] ?? '').' '.($person['last_name'] ?? '')),
                    ];
                    foreach (['id', 'start_date', 'end_date', 'notes', 'is_contact', 'training_level_1', 'training_level_2'] as $field) {
                        if (array_key_exists($field, $item)) {
                            $member[$field] = in_array($field, ['start_date', 'end_date'], true)
                                ? $this->normalizeMemberDate($item[$field]) : $item[$field];
                        }
                    }
                    foreach (['first_name', 'last_name', 'email'] as $field) {
                        if (array_key_exists($field, $person)) {
                            $member[$field] = $person[$field];
                        }
                    }
                    $uuidField = $application ? 'uuid' : 'person_uuid';
                    if (array_key_exists($uuidField, $person)) {
                        $member['person_uuid'] = $person[$uuidField];
                    }
                    // Only explicitly captured presentation data; never enrich from current models.
                    if (!$application) {
                        foreach (['institution', 'credentials', 'expertises'] as $field) {
                            if (array_key_exists($field, $item)) {
                                $member[$field] = $item[$field];
                            }
                        }
                    } else {
                        foreach (['credentials', 'expertises'] as $field) {
                            $path = 'relations.person.relations.'.$field;
                            if (Arr::has($entry, $path) && is_array(data_get($entry, $path))) {
                                $member[$field] = array_values(array_filter(array_map(
                                    fn ($value) => data_get($value, 'attributes.name'), data_get($entry, $path)
                                ), fn ($value) => is_string($value)));
                            }
                        }
                    }
                    $roles = $application ? data_get($entry, 'relations.roles') : ($item['roles'] ?? null);
                    if (is_array($roles) && array_is_list($roles)) {
                        $member['roles'] = array_map(function ($role) use ($application) {
                            $attributes = $application ? ($role['attributes'] ?? []) : $role;
                            $normalized = [
                                'name' => $attributes['name'] ?? null,
                                'label' => $attributes['display_name'] ?? $attributes['name'] ?? null,
                            ];
                            if (array_key_exists('id', $attributes)) {
                                $normalized['id'] = $attributes['id'];
                            }
                            return $normalized;
                        }, $roles);
                    }
                    $items[] = $member;
                }
            }
            $result[$key] = $items;
        }
        return $result;
    }

    private function normalizeMemberDate(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }
        try {
            return $value ? \Carbon\Carbon::parse($value)->toISOString() : $value;
        } catch (\Exception) {
            return $value;
        }
    }

    private function normalizeGeneValue(string $field, mixed $value): mixed
    {
        if ($field === 'tier' && ($value === '' || $value === 'null')) {
            return null;
        }
        if ($field === 'plan') {
            // Application model snapshots contain raw JSON; Scope snapshots contain arrays.
            if (is_string($value)) {
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $value = $decoded;
                }
            }
            return $this->canonicalArray($value);
        }
        if ($field === 'date_approved' && $value) {
            try {
                return \Carbon\Carbon::parse($value)->toISOString();
            } catch (\Exception) {
                // Keep malformed historical text visible rather than inventing a date.
            }
        }
        return is_scalar($value) ? (string) $value : $value;
    }

    private function canonicalArray(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }
        if (!array_is_list($value)) {
            ksort($value);
        }
        return array_map(fn ($item) => $this->canonicalArray($item), $value);
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
