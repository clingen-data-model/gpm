<?php

namespace App\Actions;

use App\Models\ForeignComponentMember;
use App\Modules\Person\Models\Person;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class ForeignComponentsRefresh
{
    use AsAction;

    public string $commandSignature = 'foreign-components:refresh';
    public string $commandDescription = 'Refresh current Foreign Component report membership data.';

    private const EXCLUDED_COUNTRY_IDS = [
        // United States and U.S. territories.
        4, 89, 161, 174, 226, 227, 234,
        // Countries handled separately.
        44, 98, 103, 114, 178,
    ];

    public function handle(): int
    {
        $connection = DB::connection();
        $queryLogEnabled = $connection->logging();
        $connection->disableQueryLog();

        try {
            // Publish the refresh atomically; a failed chunk must not leave a partial report.
            return $connection->transaction(function () {
                ForeignComponentMember::query()->where('is_in_scope', true)
                    ->update(['is_in_scope' => false]);
                $count = 0;

                Person::query()
                    ->select(['people.id', 'people.uuid', 'people.institution_id', 'people.city', 'people.country_id'])
                    ->whereHas('memberships')
                    ->where(function ($query) {
                        $query->where(function ($query) {
                            $query->whereNotNull('people.country_id')
                                ->whereNotIn('people.country_id', self::EXCLUDED_COUNTRY_IDS);
                        })->orWhere(function ($query) {
                            $query->whereNull('people.country_id')
                                ->whereHas('institution', function ($query) {
                                    $query->whereNotNull('country_id')
                                        ->whereNotIn('country_id', self::EXCLUDED_COUNTRY_IDS);
                                });
                        });
                    })
                    ->withMin('memberships', 'start_date')
                    ->withMax('memberships', 'end_date')
                    ->withCount(['memberships as active_membership_count' => fn ($query) => $query->whereNull('end_date')])
                    ->with(['country:id,name', 'institution:id,name,city,country_id', 'institution.country:id,name'])
                    ->chunkById(1000, function ($people) use (&$count) {
                        $rows = $people->map(function ($person) {
                            $personLocation = $person->country_id !== null;

                            return [
                                'person_uuid' => $person->uuid,
                                'organization_name' => $person->institution?->name,
                                'city' => $personLocation ? $person->city : $person->institution?->city,
                                'country_id' => $personLocation ? $person->country_id : $person->institution?->country_id,
                                'country_name' => $personLocation ? $person->country?->name : $person->institution?->country?->name,
                                'location_source' => $personLocation ? 'person' : 'institution',
                                'added_at' => $person->memberships_min_start_date,
                                'removed_at' => $person->active_membership_count > 0 ? null : $person->memberships_max_end_date,
                                'active_membership_count' => $person->active_membership_count,
                                'is_in_scope' => true,
                            ];
                        })->all();

                        // Stream-owned fields are deliberately absent from both insert and update lists.
                        ForeignComponentMember::upsert($rows, ['person_uuid'], [
                            'organization_name', 'city', 'country_id', 'country_name',
                            'location_source', 'added_at', 'removed_at', 'active_membership_count', 'is_in_scope',
                        ]);
                        $count += count($rows);
                    }, 'people.id', 'id');

                return $count;
            });
        } finally {
            if ($queryLogEnabled) {
                $connection->enableQueryLog();
            }
        }
    }

    public function asCommand(Command $command): void
    {
        $command->info('Refreshed '.$this->handle().' Foreign Component members.');
    }
}
