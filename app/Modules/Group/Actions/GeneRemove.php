<?php

namespace App\Modules\Group\Actions;

use App\Modules\ExpertPanel\Models\Gene;
use App\Modules\Group\Events\GeneRemoved;
use App\Modules\Group\Models\Group;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsController;
use Lorisleiva\Actions\Concerns\AsObject;

class GeneRemove
{
    use AsController;
    use AsObject;

    public function handle(Group $group, Gene $gene): Group
    {
        $gene->delete();

        event(new GeneRemoved($group, $gene));

        return $group;
    }

    public function asController(Request $request, $groupUuid, $geneId)
    {
        $group = Group::findByUuidOrFail($groupUuid);
        if (Auth::user()->cannot('removeGene', $group)) {
            throw new AuthorizationException('You do not have permission to remove genes from this expert panel\'s gene list.');
        }

        if (! $group->isExpertPanel) {
            throw ValidationException::withMessages(['group' => ['Only expert panels have genes.  You can not remove a gene from a '.$group->type->full_name]]);
        }

        $gene = $group->expertPanel->genes()->findOrFail($geneId);

        $group = $this->handle($group, $gene);
    }
}
