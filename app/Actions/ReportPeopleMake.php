<?php

namespace App\Actions;

use App\Models\Person;
use Illuminate\Support\Facades\DB;

class ReportPeopleMake extends ReportMakeAbstract
{
    public $commandSignature = 'report:people';

    public function handle(): array
    {
        $peopleData = [];

        // Fetch groups and group types
        $groupDetails = DB::table('groups')
            ->leftJoin('group_types', 'groups.group_type_id', '=', 'group_types.id')
            ->select('groups.id', 'groups.name', 'group_types.name as group_type')
            ->get()
            ->keyBy('id');

        // Ensure the result is processed as an array
        Person::with([
            'memberships' => function ($q) {
                $q->isActive();
            },
            'memberships.group',
            'memberships.group.type',
            'memberships.roles',
            'country',
            'credentials',
            'expertises',
        ])->chunk(100, function ($people) use (&$peopleData, $groupDetails) {
            foreach ($people as $person) {
                $data = [
                    'id' => $person->id,
                    'name' => $person->first_name . ' ' . $person->last_name,
                    'email' => $person->email,
                    'institution' => $person->institution ? $person->institution->name : null,
                    'state' => $person->state ? $person->state : null,
                    'country' => $person->country ? $person->country->name : null,
                    'timezone' => $person->timezone,
                    'phone' => $person->phone,
                    'credentials' => $person->credentialsAsString,
                    'expertises' => preg_replace('/\n/', '; ', $person->expertisesAsString),
                    'active_groups' => $person->memberships->map(function ($membership) use ($groupDetails) {
                        $group = $groupDetails->get($membership->group_id);
                        $roles = $membership->roles->pluck('name')->join(', ');
                        if ($group) {
                            return $group->name . ' (' . ($roles ?: 'No Roles') . ') - ' . ($group->group_type ?? 'No Type');
                        }
                        return null;
                    })->filter()->join("; "),
                    'biography' => $person->biography,
                    'orcid_id' => $person->orcid_id,
                    'hypothesis_id' => $person->hypothesis_id,
                    'has_registered' => $person->user_id ? 'yes' : 'no',
                    'date_created' => $person->created_at->format('Y-m-d'),
                    'last_updated' => $person->updated_at->format('Y-m-d'),
                ];

                $peopleData[] = $data;
            }
        });

        return $peopleData;
    }
}
