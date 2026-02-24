<?php

namespace App\Actions;

use Illuminate\Support\Facades\DB;

class ReportDemographicsMake extends ReportMakeAbstract
{
    public $commandSignature = 'reports:demographics';

    private static $multiValueFields = [
        'ethnicities',
        'identities',
        'gender_identities',
        'support',
        'occupations',
    ];

    private static $singleValueFields = [
        'birth_country',
        'reside_country',
        'reside_state',
        'age_category',
        'institution',
    ];

    public function csvHeaders(): ?array
    {
        return ['subset','field','value','count'];
    }

    public function streamRows(callable $push): void
    {
        DB::connection()->disableQueryLog();

        $counts = [];

        $this->streamAll(function ($subset, $row) use (&$counts) {
            $this->tallyRow($counts, $subset, $row);
        });

        foreach (['vcep','gcep','cdwg','wg'] as $gt) {
            $this->streamGroupType($gt, function ($subset, $row) use (&$counts) {
                $this->tallyRow($counts, $subset, $row);
            });
        }

        foreach ($this->flattenCounts($counts) as $record) {
            $push($record);
        }
    }

    private function streamAll(callable $cb): void
    {
        $seenPerson = [];
        DB::table('v_profile_demographics')
            ->whereNull('deleted_at')
            ->whereNotNull('demographics_completed_date')
            ->orderBy('person_id')
            ->select(array_merge(
                ['person_id'],
                self::$singleValueFields,
                self::$multiValueFields
            ))
            ->chunk(2000, function ($rows) use (&$seenPerson, $cb) {
                foreach ($rows as $d) {
                    foreach (self::$multiValueFields as $f) {
                        $d->$f = $d->$f ? json_decode($d->$f, true) : [];
                    }
                    $cb('all', $d);
                    if (!isset($seenPerson[$d->person_id])) {
                        $seenPerson[$d->person_id] = true;
                        $cb('all_person', $d);
                    }
                }
            });
    }

    private function streamGroupType(string $groupType, callable $cb): void
    {
        $seenPerson = [];
        $seenBioPerson = [];
        $seenCoorPerson = [];
        $seenChairPerson = [];

        DB::table('v_group_profile_demographics')
            ->whereNull('deleted_at')
            ->whereNotNull('demographics_completed_date')
            ->where('group_type', '=', $groupType)
            ->orderBy('person_id')
            ->select(array_merge(
                ['person_id','role'],
                self::$singleValueFields,
                self::$multiValueFields
            ))
            ->chunk(2000, function ($rows) use ($groupType, &$seenPerson, &$seenBioPerson, &$seenCoorPerson, &$seenChairPerson, $cb) {
                foreach ($rows as $d) {
                    foreach (self::$multiValueFields as $f) {
                        $d->$f = $d->$f ? json_decode($d->$f, true) : [];
                    }

                    $cb($groupType, $d);

                    if (!isset($seenPerson[$d->person_id])) {
                        $seenPerson[$d->person_id] = true;
                        $cb($groupType.'_person', $d);
                    }

                    if ($d->role === 'biocurator') {
                        $cb($groupType.'_biocurator', $d);
                        if (!isset($seenBioPerson[$d->person_id])) {
                            $seenBioPerson[$d->person_id] = true;
                            $cb($groupType.'_biocurator_person', $d);
                        }
                    }

                    if ($d->role === 'coordinator') {
                        $cb($groupType.'_coordinator', $d);
                        if (!isset($seenCoorPerson[$d->person_id])) {
                            $seenCoorPerson[$d->person_id] = true;
                            $cb($groupType.'_coordinator_person', $d);
                        }
                    }

                    if ($d->role === 'chair') {
                        $cb($groupType.'_chair', $d);
                        if (!isset($seenChairPerson[$d->person_id])) {
                            $seenChairPerson[$d->person_id] = true;
                            $cb($groupType.'_chair_person', $d);
                        }
                    }
                }
            });
    }

    private function tallyRow(array &$counts, string $subset, $row): void
    {
        foreach (self::$singleValueFields as $f) {
            $val = $row->$f ?? '';
            $this->inc($counts, $subset, $f, (string) $val);
        }

        foreach (self::$multiValueFields as $f) {
            $arr = is_array($row->$f) ? $row->$f : [];
            if (count($arr) === 0) {
                $this->inc($counts, $subset, $f, '');
            } else {
                foreach ($arr as $val) {
                    $this->inc($counts, $subset, $f, (string) $val);
                }
            }
        }
    }

    private function inc(array &$counts, string $subset, string $field, string $value): void
    {
        if (!isset($counts[$subset])) $counts[$subset] = [];
        if (!isset($counts[$subset][$field])) $counts[$subset][$field] = [];
        if (!isset($counts[$subset][$field][$value])) $counts[$subset][$field][$value] = 0;
        $counts[$subset][$field][$value]++;
    }

    private function flattenCounts(array $counts): \Generator
    {
        foreach ($counts as $subset => $fields) {
            foreach ($fields as $field => $values) {
                arsort($values);
                foreach ($values as $value => $count) {
                    yield [
                        'subset' => $subset,
                        'field'  => $field,
                        'value'  => $value,
                        'count'  => $count,
                    ];
                }
            }
        }
    }
}
