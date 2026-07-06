<?php

namespace App\Modules\Group\Actions;

use App\Modules\Group\Models\AffiliationIdSequence;
use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;

class WorkingGroupAffiliationIdGenerate
{
    use AsAction;

    public function handle(Group $group): string
    {
        if (!$group->isWorkingGroupType) {
            throw ValidationException::withMessages(['group_type_id' => 'Only Working Group, CDWG, and SC-CDWG groups can receive generated Affiliation IDs.']);
        }
        if ($group->affiliation_id) { return $group->affiliation_id; }

        return DB::transaction(function () use ($group) {
            $sequence = AffiliationIdSequence::query()->where('name', 'working_group')->lockForUpdate()->firstOrFail();
            $nextValue = (int) $sequence->next_value;
            while ($nextValue <= 69999) {
                $affiliationId = (string) $nextValue;
                $exists = Group::query()->where('affiliation_id', $affiliationId)->exists();
                if (!$exists) {
                    $group->update(['affiliation_id' => $affiliationId]);
                    $sequence->update(['next_value' => $nextValue + 1]);
                    return $affiliationId;
                }
                $nextValue++;
            }
            throw ValidationException::withMessages(['affiliation_id' => 'The Working Group Affiliation ID range has been exhausted.']);
        });
    }
}