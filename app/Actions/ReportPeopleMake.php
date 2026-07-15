<?php

namespace App\Actions;

use App\Modules\Person\Models\Person;
use Illuminate\Support\Facades\DB;

class ReportPeopleMake extends ReportMakeAbstract
{
    public $commandSignature = 'report:people';

    public function handle()
    {
        return [];
    }

    public function csvHeaders(): ?array
    {
        return [
            'id','first_name','last_name','email','institution','city','state','country','timezone','phone',
            'credentials','expertises','active_groups', 'active_status', 'biography','orcid_id',
            'hypothesis_id','has_registered','date created','last_updated'
        ];
    }

    public function streamRows(callable $push): void
    {
        $connection = app('db')->connection();
        $queryLogEnabled = $connection->logging();
        $connection->disableQueryLog();
        try {
            Person::query()
                ->select([
                    'id','first_name','last_name','email','institution_id', 'city', 'state','country_id',
                    'timezone','phone','biography','orcid_id','hypothesis_id','user_id',
                    'created_at','updated_at'
                ])
                ->withCount([
                    'memberships as active_memberships_count' => function ($query) {
                        $query->isActive();
                    },
                ])
                ->with([
                    'memberships' => function ($q) {
                        $q->isActive()
                        ->select(['id','group_id','person_id'])
                        ->with([
                            'group:id,group_type_id,name',
                            'group.type:id,name',
                            'roles:id,name',
                        ]);
                    },
                    'country:id,name',
                    'credentials:id,name',
                    'expertises:id,name',
                    'institution:id,name',
                ])
                ->orderBy('id')
                ->chunkById(1000, function ($people) use ($push) {
                    foreach ($people as $person) {
                        $activeGroups = $person->memberships->map(function ($m) {
                            $label = trim(($m->group->name ?? '').' '.strtoupper(optional($m->group->type)->name));
                            $roles = $m->roles->pluck('name')->filter()->implode(', ');
                            return $roles ? "{$label} ({$roles})" : $label;
                        })->filter()->implode('; ');

                        $isActive = !is_null($person->user_id) && $person->active_memberships_count > 0;
                        $push([
                            'id'             => $person->id,
                            'first_name'     => trim($person->first_name),
                            'last_name'      => trim($person->last_name),
                            'email'          => $person->email,
                            'institution'    => optional($person->institution)->name,
                            'city'           => $person->city ?: null,
                            'state'          => $person->state ?: null,
                            'country'        => optional($person->country)->name,
                            'timezone'       => $person->timezone,
                            'phone'          => $person->phone,
                            'credentials'    => $person->credentialsAsString ?? '',
                            'expertises'     => preg_replace('/\r?\n/', '; ', $person->expertisesAsString ?? ''),
                            'active_groups'  => $activeGroups,
                            'active_status'  => $isActive ? 'Active' : 'Inactive',
                            'biography' => $this->formatBiography($person->biography),
                            'orcid_id'       => $person->orcid_id,
                            'hypothesis_id'  => $person->hypothesis_id,
                            'has_registered' => $person->user_id ? 'yes' : 'no',
                            'date created'   => optional($person->created_at)?->format('Y-m-d'),
                            'last_updated'   => optional($person->updated_at)?->format('Y-m-d'),
                        ]);
                    }

                    $people->each->unsetRelations();
                    gc_collect_cycles();
                });
        } finally {
            if ($queryLogEnabled) {
                $connection->enableQueryLog();
            }
        }
    }

    private function formatBiography(?string $biography): string
    {
        if (!$biography) {
            return '';
        }

        $biography = strip_tags($biography);
        $biography = html_entity_decode($biography, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $biography = preg_replace('/\s+/u', ' ', $biography);
        $biography = trim($biography);

        if (mb_strlen($biography, 'UTF-8') > 32000) {
            return mb_substr($biography, 0, 31985, 'UTF-8').' [truncated]';
        }

        return $biography;
    }
}
