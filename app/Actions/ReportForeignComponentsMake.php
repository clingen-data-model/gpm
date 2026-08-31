<?php

namespace App\Actions;

use Carbon\Carbon;
use App\Modules\Person\Models\Person;
use App\Modules\Person\Models\Institution;

class ReportForeignComponentsMake extends ReportMakeAbstract
{
    public $commandSignature = 'report:foreign-components';

    private const FOREIGN_COUNTRY_IDS = [
        44,  // China
        98,  // Hong Kong
        103, // Iran (Islamic Republic of)
        114, // Korea, Democratic People's Republic of
        178, // Russian Federation
    ];

    public function handle()
    {
        return [];
    }

    public function csvHeaders(): ?array
    {
        return [
            'Organization name',
            'City',
            'Country',
            'Name(s) of individual(s) in the group',
            'Added date',
            'Removed date',
            'Status',
            'Has this foreign site already been approved for this award? (Y/N)',
            'Will my NIH-funded work lead to a publication with a foreign scientist? (Y/N)',
            'Does the foreign site play a role in the goals and objectives of the NIH award beyond the joint publication? (Y/N; If yes, please describe.)',
            'Will funds from the NIH award be used abroad? (Y/N; If yes, please describe.)',
            'Will human subjects, vertebrate animals, select agents and toxins work for this award take place at the foreign site? (Y/N; If yes, please describe.)',
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
                    'people.id',
                    'people.first_name',
                    'people.last_name',
                    'people.institution_id',
                    'people.city',
                    'people.country_id',
                ])                
                ->whereHas('memberships')           // A person must have been a member of at least one GPM group.                
                ->where(function ($query) {         // Determine foreign-component eligibility: use person's country when available, otherwise fall back to institution country.
                    $query
                        ->whereIn('people.country_id', self::FOREIGN_COUNTRY_IDS)
                        ->orWhere(function ($query) {
                            $query
                                ->whereNull('people.country_id')
                                ->whereHas('institution', function ($institutionQuery) {
                                    $institutionQuery->whereIn(
                                        'country_id',
                                        self::FOREIGN_COUNTRY_IDS
                                    );
                                });
                        });
                })

                // Earliest membership is when they were first added to GPM.
                ->withMin('memberships', 'start_date')

                // Latest end date is used only when they currently have no active memberships.
                ->withMax('memberships', 'end_date')

                // Any membership without an end date means the person is still active somewhere in GPM.
                ->withCount([
                    'memberships as active_memberships_count' => function ($query) {
                        $query->isActive();
                    },
                ])

                ->with([
                    'country:id,name',
                    'institution:id,name,city,country_id',
                    'institution.country:id,name',
                ])

                // Sort primarily by organization, then individual.
                ->orderBy(
                    Institution::query()
                        ->select('name')
                        ->whereColumn('institutions.id', 'people.institution_id')
                )
                ->orderBy('people.last_name')
                ->orderBy('people.first_name')
                ->orderBy('people.id')
                ->chunk(1000, function ($people) use ($push) {
                    foreach ($people as $person) {
                        $hasPersonCountry = !is_null($person->country_id);

                        $city = $hasPersonCountry ? $person->city : $person->institution?->city;
                        $country = $hasPersonCountry ? $person->country?->name : $person->institution?->country?->name;
                        $isActive = $person->active_memberships_count > 0;
                        $addedDate = $person->memberships_min_start_date ? Carbon::parse($person->memberships_min_start_date)->format('Y-m-d') : null;
                        $removedDate = !$isActive && $person->memberships_max_end_date ? Carbon::parse($person->memberships_max_end_date)->format('Y-m-d') : null;
                        $push([
                            'Organization name' => $person->institution?->name,
                            'City' => $city,
                            'Country' => $country,
                            'Name(s) of individual(s) in the group' => trim($person->first_name . ' ' . $person->last_name),
                            'Added date' => $addedDate,
                            'Removed date' => $removedDate,
                            'Status' => $isActive ? 'Active' : 'Removed',
                            'Has this foreign site already been approved for this award? (Y/N)' => 'N',
                            'Will my NIH-funded work lead to a publication with a foreign scientist? (Y/N)' => 'Y',
                            'Does the foreign site play a role in the goals and objectives of the NIH award beyond the joint publication? (Y/N; If yes, please describe.)' => 'Y',
                            'Will funds from the NIH award be used abroad? (Y/N; If yes, please describe.)' => 'N',
                            'Will human subjects, vertebrate animals, select agents and toxins work for this award take place at the foreign site? (Y/N; If yes, please describe.)' => 'N',
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
}