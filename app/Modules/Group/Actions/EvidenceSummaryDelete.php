<?php

namespace App\Modules\Group\Actions;

use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\Auth;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsObject;
use Lorisleiva\Actions\Concerns\AsController;
use Illuminate\Auth\Access\AuthorizationException;
use App\Modules\ExpertPanel\Models\EvidenceSummary;
use App\Modules\Group\Events\EvidenceSummaryDeleted;

class EvidenceSummaryDelete
{
    use AsObject;
    use AsController;

    public function handle(Group $group, EvidenceSummary $summary): void
    {
        $summary->delete();
        
        event(new EvidenceSummaryDeleted($group, $summary));
    }

    public function asController(ActionRequest $request, $groupUuid, $summaryId)
    {
        $group = Group::findByUuidOrFail($groupUuid);
        if (!$group->isVcepOrScvcep) {
            return;
        }
        $evidenceSummary = $group->expertPanel->evidenceSummaries()->findOrFail($summaryId);
        
        if (Auth::user()->cannot('updateEvidenceSummary', $group)) {
            throw new AuthorizationException('You do not have permission to update an example evidence summary for this VCEP.');
        }

        $this->handle($group, $evidenceSummary);

        return;
    }
}
