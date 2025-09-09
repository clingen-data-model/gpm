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
            'id','name','email','institution','state','country','timezone','phone',
            'credentials','expertises','active_groups','biography','orcid_id',
            'hypothesis_id','has_registered','date created','last_updated'
        ];
    }

    public function streamRows(callable $push): void
    {
        DB::connection()->disableQueryLog();

        Person::query()
            ->whereNull('deleted_at')
            ->select([
                'id','first_name','last_name','email','institution_id','state','country_id',
                'timezone','phone','biography','orcid_id','hypothesis_id','user_id',
                'created_at','updated_at'
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

                    $push([
                        'id'             => $person->id,
                        'name'           => trim($person->first_name.' '.$person->last_name),
                        'email'          => $person->email,
                        'institution'    => optional($person->institution)->name,
                        'state'          => $person->state ?: null,
                        'country'        => optional($person->country)->name,
                        'timezone'       => $person->timezone,
                        'phone'          => $person->phone,
                        'credentials'    => $person->credentialsAsString ?? '',
                        'expertises'     => preg_replace('/\r?\n/', '; ', $person->expertisesAsString ?? ''),
                        'active_groups'  => $activeGroups,
                        'biography'      => $person->biography,
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
    }
}
