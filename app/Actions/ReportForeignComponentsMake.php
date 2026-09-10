<?php

namespace App\Actions;

use App\Models\ForeignComponentMember;
use App\Modules\Person\Models\Person;

class ReportForeignComponentsMake extends ReportMakeAbstract
{
    public $commandSignature = 'report:foreign-components';

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
            'Unretired date',
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
            ForeignComponentMember::query()
                ->leftJoinSub(
                    Person::query()->select(['uuid', 'first_name', 'last_name']),
                    'report_people',
                    'report_people.uuid',
                    '=',
                    'foreign_component_members.person_uuid'
                )
                ->select(['foreign_component_members.*', 'report_people.first_name', 'report_people.last_name'])
                ->where('is_in_scope', true)
                ->orderBy('organization_name')
                ->orderBy('report_people.last_name')
                ->orderBy('report_people.first_name')
                ->orderBy('foreign_component_members.id')
                ->chunk(1000, function ($members) use ($push) {
                    foreach ($members as $member) {
                        $push([
                            'Organization name' => $member->organization_name,
                            'City' => $member->city,
                            'Country' => $member->country_name,
                            'Name(s) of individual(s) in the group' => trim($member->first_name . ' ' . $member->last_name),
                            'Added date' => $member->added_at?->format('Y-m-d'),
                            'Unretired date' => $member->last_unretired_at?->format('Y-m-d'),
                            'Removed date' => $member->removed_at?->format('Y-m-d'),
                            'Status' => $member->active_membership_count > 0 ? 'Active' : 'Removed',
                            'Has this foreign site already been approved for this award? (Y/N)' => 'N',
                            'Will my NIH-funded work lead to a publication with a foreign scientist? (Y/N)' => 'Y',
                            'Does the foreign site play a role in the goals and objectives of the NIH award beyond the joint publication? (Y/N; If yes, please describe.)' => 'Y',
                            'Will funds from the NIH award be used abroad? (Y/N; If yes, please describe.)' => 'N',
                            'Will human subjects, vertebrate animals, select agents and toxins work for this award take place at the foreign site? (Y/N; If yes, please describe.)' => 'N',
                        ]);
                    }

                    $members->each->unsetRelations();
                    gc_collect_cycles();
                });
        } finally {
            if ($queryLogEnabled) {
                $connection->enableQueryLog();
            }
        }
    }
}
