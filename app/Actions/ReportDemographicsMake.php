<?php

namespace App\Actions;

use Illuminate\Support\Facades\DB;

class ReportDemographicsMake extends ReportMakeAbstract
{

    public $commandSignature = 'reports:demographics';

    // FIXME: it would be nice to get these directly from the Person model...
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

    private function countSingleValuedFields(String $subset, $data)
    {
        $counts = [];
        foreach (self::$singleValueFields as $f) {
            $data->countBy($f)->sort()->reverse()->map(function ($count, $value) use ($f, $subset, &$counts) {
                array_push($counts, [
                    "subset" => $subset,
                    "field" => $f,
                    "value" => $value,
                    "count" => $count,
                ]);
            });
        }
        return $counts;
    }

    private function countMultiValuedFields(String $subset, $data)
    {
        $multiValueCounts = [];
        foreach (self::$multiValueFields as $f) {
            $multiValueCounts[$f] = [];
        };

        foreach ($data as $p) {
            foreach (self::$multiValueFields as $f) {
                if ($p->$f) {
                    foreach ($p->$f as $value) {
                        $multiValueCounts[$f][$value] = ($multiValueCounts[$f][$value] ?? 0) + 1;
                    }
                } else {
                    $multiValueCounts[$f][''] = ($multiValueCounts[$f][''] ?? 0) + 1;
                }
            };
        }

        $counts = [];
        foreach ($multiValueCounts as $f => $mvCounts) {
            $mvCounts = collect($mvCounts)->sort()->reverse();
            foreach ($mvCounts as $value => $count) {
                array_push($counts, [
                    "subset" => $subset,
                    "field" => $f,
                    "value" => $value,
                    "count" => $count,
                ]);
            }
        }
        return $counts;
    }

    private function countsForGroupType(String $groupType)
    {
        $group_subset = DB::table('v_group_profile_demographics')
            ->whereNull('deleted_at')
            ->whereNotNull('demographics_completed_date')
            ->where('group_type', '=', $groupType)
            ->get()
            ->transform(function ($d) {
                foreach (self::$multiValueFields as $f) {
                    $d->$f = $d->$f ? json_decode($d->$f) : [];
                }
                return $d;
            });
        $counts = self::countSingleValuedFields($groupType, $group_subset);
        $counts = array_merge($counts, self::countMultiValuedFields($groupType, $group_subset));

        $subset = $group_subset->unique('person_id');
        $counts = array_merge($counts, self::countSingleValuedFields($groupType . '_person', $subset));
        $counts = array_merge($counts, self::countMultiValuedFields($groupType . '_person', $subset));

        $subset = $group_subset->where('role', 'biocurator');
        $counts = array_merge($counts, self::countSingleValuedFields($groupType . '_biocurator', $subset));
        $counts = array_merge($counts, self::countMultiValuedFields($groupType . '_biocurator', $subset));

        $subset = $subset->unique('person_id');
        $counts = array_merge($counts, self::countSingleValuedFields($groupType . '_biocurator_person', $subset));
        $counts = array_merge($counts, self::countMultiValuedFields($groupType . '_biocurator_person', $subset));

        $subset = $group_subset->where('role', 'coordinator');
        $counts = array_merge($counts, self::countSingleValuedFields($groupType . '_coordinator', $subset));
        $counts = array_merge($counts, self::countMultiValuedFields($groupType . '_coordinator', $subset));

        $subset = $subset->unique('person_id');
        $counts = array_merge($counts, self::countSingleValuedFields($groupType . '_coordinator_person', $subset));
        $counts = array_merge($counts, self::countMultiValuedFields($groupType . '_coordinator_person', $subset));

        $subset = $group_subset->where('role', 'chair');
        $counts = array_merge($counts, self::countSingleValuedFields($groupType . '_chair', $subset));
        $counts = array_merge($counts, self::countMultiValuedFields($groupType . '_chair', $subset));

        $subset = $subset->unique('person_id');
        $counts = array_merge($counts, self::countSingleValuedFields($groupType . '_chair_person', $subset));
        $counts = array_merge($counts, self::countMultiValuedFields($groupType . '_chair_person', $subset));

        return $counts;
    }

    public function handle(): array
    {
        $subset = DB::table('v_profile_demographics')
            ->whereNull('deleted_at')
            ->whereNotNull('demographics_completed_date')
            ->get()
            ->transform(function ($d) {
                foreach (self::$multiValueFields as $f) {
                    $d->$f = $d->$f ? json_decode($d->$f) : [];
                }
                return $d;
            });

        $counts = self::countSingleValuedFields('all', $subset);
        $counts = array_merge($counts, self::countMultiValuedFields('all', $subset));

        $counts = array_merge($counts, self::countsForGroupType('vcep'));
        $counts = array_merge($counts, self::countsForGroupType('gcep'));
        $counts = array_merge($counts, self::countsForGroupType('cdwg'));
        $counts = array_merge($counts, self::countsForGroupType('wg'));

        return $counts;
    }
}
