<?php

namespace App\Modules\Group\Actions;

use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\Auth;
use App\Services\HgncLookupInterface;
use Lorisleiva\Actions\ActionRequest;
use App\Services\DiseaseLookupInterface;
use App\Modules\ExpertPanel\Models\Gene;
use Lorisleiva\Actions\Concerns\AsObject;
use Lorisleiva\Actions\Concerns\AsController;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use App\Modules\Group\Http\Resources\GroupResource;

use function Psy\debug;

class GeneUpdate
{
    use AsObject;
    use AsController;

    public function __construct(private HgncLookupInterface $hgncLookup, private DiseaseLookupInterface $mondoLookup)
    {
    }
    

    public function handle(Group $group, Gene $gene, array $data): Group
    {
        // $data['gene_symbol'] = $this->hgncLookup->findSymbolById($data['hgnc_id']);
        // $data['disease_name'] = $this->mondoLookup->findNameByOntologyId($data['mondo_id']);
        $gene->update($data);

        return $group;
    }

    public function asController(ActionRequest $request, Group $group, $geneId)
    {
        $gene = $group->expertPanel->genes()->findOrFail($geneId);

        if (!$group->isEp) {
            throw ValidationException::withMessages(['group' => ['Only expert panels have a gene list.  You can not update a gene on a '.$group->type->full_name]]);
        }

        $group = $this->handle($group, $gene, $request->all());

        $group->load(['expertPanel.genes', 'members', 'members.permissions', 'members.roles', 'members.cois', 'members.person']);

        return new GroupResource($group);
    }

    public function authorize(ActionRequest $request, Group $group): bool
    {
        if (Auth::user()->cannot('updateGene', $group)) {
            return false;
        }

        return true;
    }
    

    public function rules(ActionRequest $request): array
    {        
        $rules = [
            'hgnc_id' => 'required|numeric',
        ];

        $group = $request->group;
        if ($group->isVcepOrScvcep) {
            $rules['mondo_id'] = 'nullable|regex:/MONDO:\d{7}$/i';
        }

        return $rules;
    }

    public function getValidationMessages(): array
    {
        return [
            'hgnc_id.required' => 'The gene is required.',
            'hgnc_id.numeric' => 'The selection is invalid.',
            'hgnc_id.exists' => 'The selection is invalid.',
            'mondo_id.required' => 'The disease is required.',
            'mondo_id.regex' => 'The selection is invalid.',
            'mondo_id.exists' => 'The selection is invalid.',
        ];
    }
}
