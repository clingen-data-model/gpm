<?php

namespace App\Modules\Group\Actions;

use App\Modules\Group\Models\Group;
use Lorisleiva\Actions\ActionRequest;
use App\Modules\ExpertPanel\Models\Gene;
use App\Modules\Group\Http\Resources\GroupResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsObject;
use Lorisleiva\Actions\Concerns\AsController;

class GeneUpdate
{
    use AsObject;
    use AsController;

    public function handle(Group $group, Gene $gene, array $data): Group
    {
        $gene->update($data);

        return $group;
    }

    public function asController(ActionRequest $request, $groupUuid, $geneId)
    {
        $group = Group::findByUuidOrFail($groupUuid);
        $gene = $group->expertPanel->genes()->findOrFail($geneId);

        if (Auth::user()->cannot('updateGene', $group)) {
            throw new AuthorizationException('You do not have permission to update this group\'s gene-list.');
        }
        
        if (!$group->isEp) {
            throw ValidationException::withMessages(['group' => ['Only expert panels have a gene list.  You can not update a gene on a '.$group->type->full_name]]);
        }

        $group = $this->handle($group, $gene, $request->all());

        $group->load(['genes', 'members', 'members.permissions', 'members.roles', 'members.cois', 'members.person']);

        return new GroupResource($group);
    }

    public function rules(): array
    {
        $rules = [
            'hgnc_id' => 'required|numeric',
        ];

        $group = Group::findByUuidOrFail(request()->uuid);
        if ($group->isVcep) {
            $rules['mondo_id'] = 'required|regex:/MONDO:\d\d\d\d\d\d\d/i';
        }

        return $rules;
    }
}
