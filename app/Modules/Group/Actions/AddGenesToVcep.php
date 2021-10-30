<?php

namespace App\Modules\Group\Actions;

use Illuminate\Http\Request;
use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\Auth;
use Lorisleiva\Actions\ActionRequest;
use App\Modules\ExpertPanel\Models\Gene;
use App\Modules\Group\Events\GenesAdded;
use Illuminate\Support\Facades\Validator;
use Lorisleiva\Actions\Concerns\AsAction;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;

class AddGenesToVcep
{
    use AsAction;

    public function handle(Group $group, array $genes): Group
    {
        if (!$group->isVcep) {
            throw ValidationException::withMessages(['group' => 'The group is not a VCEP.']);
        }

        foreach ($genes as $gene) {
            $group->expertPanel->genes()
                ->create([
                    'hgnc_id' => $gene['hgnc_id'],
                    'mondo_id' => $gene['mondo_id'],
                    'expert_panel_id' => $group->expertPanel->id,
                    'gene_symbol' => $this->getGeneSymbolForHgncId($gene['hgnc_id'])
                ]);
        }

        event(new GenesAdded($group, $genes));
        return $group;
    }

    public function asController(ActionRequest $request, $groupUuid)
    {
        $group = Group::findByUuidOrFail($groupUuid);
        if (Auth::user()->cannot('addGene', $group)) {
            throw new AuthorizationException('You do not have permission to add genes to this expert panel\'s scope');
        }

        $this->handle($group, $request->genes);
        return $group->fresh()->load('expertPanel.genes');
    }

    public function rules(): array
    {
        return [
            'genes' => 'required|array|min:1',
            'genes.*' => 'required|array:hgnc_id,mondo_id',
            'genes.*.hgnc_id' => 'required|numeric',
            'genes.*.mondo_id' => 'required|regex:/MONDO:\d\d\d\d\d\d\d/i',
        ];
    }

    private function getGeneSymbolForHgncId($hgncId): string
    {
        return uniqid();
    }
}
