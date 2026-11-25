<?php

namespace App\Modules\Group\Actions;

use App\Modules\Group\Models\{Group, GroupMember};
use Illuminate\Support\Collection;

class InactivationContacts
{
    public function members(Group $group): Collection
    {
        return $group->members()->with(['person', 'roles'])->get()
            ->filter(fn ($member) => $member->person)
            ->map(fn ($member) => array_merge($this->contact($member), [
                'id' => $member->id,
                'first_name' => $member->person->first_name,
                'last_name' => $member->person->last_name,
                'is_active' => $member->end_date === null,
                'is_grant_liaison' => $member->hasRole(config('groups.roles.grant-liaison.name')),
            ]))->values();
    }

    public function contact(GroupMember $member): array
    {
        return [
            'member_id' => $member->id, 'person_id' => $member->person_id,
            'name' => $member->person->name, 'email' => $member->person->email,
            'role' => $member->roles->map(fn ($role) => $role->display_name
                ?: config('groups.roles.'.$role->name.'.display_name', $role->name))->implode(', '),
        ];
    }

    public function defaults(Group $group): array
    {
        return $group->members()->isActive()
            ->whereHas('roles', fn ($q) => $q->where('name', config('groups.roles.grant-liaison.name')))
            ->with(['person', 'roles'])->get()->filter(fn ($member) => $member->person)
            ->unique('person_id')->map(fn ($member) => $this->contact($member))->values()->all();
    }

    public function normalize(array $answers): array
    {
        // An explicit empty array is intentional. Never reapply role defaults on reads/saves.
        if (!array_key_exists('liaisons', $answers)) {
            $legacy = $answers['liaison'] ?? null;
            $answers['liaisons'] = is_array($legacy) && (!empty($legacy['member_id']) || !empty($legacy['name']) || !empty($legacy['email']))
                ? [$legacy] : [];
        }
        unset($answers['liaison']);
        if (is_array($answers['continuing_members'] ?? null)) {
            $answers['continuing_members'] = array_map(function ($contact) {
                if (is_array($contact) && !isset($contact['name'])) $contact['name'] = trim(($contact['first_name'] ?? '').' '.($contact['last_name'] ?? ''));
                return $contact;
            }, $answers['continuing_members']);
        }
        return $answers;
    }

    public function synchronize(Group $group, array $contacts): array
    {
        $members = $group->members()->with(['person', 'roles'])->get()->keyBy('id');
        return array_map(function ($contact) use ($members) {
            if (!is_array($contact)) return $contact; // Leave malformed input for validation.
            $id = $contact['member_id'] ?? null;
            if (is_scalar($id) && $id && ($member = $members->get($id)) && $member->person) {
                return $this->contact($member);
            }
            $contact['person_id'] = null;
            $contact['role'] = empty($id) ? 'Manual contact' : ($contact['role'] ?? null);
            return $contact;
        }, $contacts);
    }
}
