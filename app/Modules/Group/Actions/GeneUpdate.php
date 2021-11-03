<?php

namespace App\Modules\Group\Actions;

use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\Auth;
use App\Services\HgncLookupInterface;
use Lorisleiva\Actions\ActionRequest;
use App\Services\MondoLookupInterface;
use App\Modules\ExpertPanel\Models\Gene;
use Lorisleiva\Actions\Concerns\AsObject;
use Lorisleiva\Actions\Concerns\AsController;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use App\Modules\Group\Http\Resources\GroupResource;

class GeneUpdate
{
    use AsObject;
    use AsController;

    public function __construct(private HgncLookupInterface $hgncLookup, private MondoLookupInterface $mondoLookup)
    {
    }
    

    public function handle(Group $group, Gene $gene, array $data): Group
    {
        $data['gene_symbol'] = $this->hgncLookup->findSymbolById($data['hgnc_id']);
        $data['disease_name'] = $this->mondoLookup->findNameByMondoId($data['mondo_id']);
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

        $group->load(['expertPanel.genes', 'members', 'members.permissions', 'members.roles', 'members.cois', 'members.person']);

        return new GroupResource($group);
    }

    public function rules(): array
    {
        $connectionName = config('database.gt_db_connection');
        $rules = [
            'hgnc_id' => 'required|numeric|exists:'.$connectionName.'.genes,hgnc_id',
        ];

        $group = Group::findByUuidOrFail(request()->uuid);
        if ($group->isVcep) {
            $rules['mondo_id'] = 'required|regex:/MONDO:\d\d\d\d\d\d\d/i|exists:'.$connectionName.'.diseases,mondo_id';
        }

        return $rules;
    }

    public function getValidationMessages(): array
    {
        return [
            'hgnc_id.required' => 'The gene is required.',
            'hgnc_id.numeric' => 'The selected gene is invalid.',
            'hgnc_id.exists' => 'The selected gene is invalid.',
            'mondo_id.required' => 'The disease is required.',
            'mondo_id.regex' => 'The selected disease is invalid.',
            'mondo_id.exists' => 'The selected disease is invalid.',
        ];
    }
    
}
