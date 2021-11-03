<?php
namespace App\Modules\Group\Actions;

use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\Auth;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsObject;
use App\Modules\Group\Actions\GeneListGcepSync;
use App\Modules\Group\Actions\GenesAddToVcep;
use Lorisleiva\Actions\Concerns\AsController;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;

class GenesAdd
{
    use AsController;
    use AsObject;

    public function __construct(private GenesAddToVcep $addGenesToVcep, private GeneListGcepSync $addGenesToGcep)
    {
    }

    public function handle(Group $group, $genes): Group
    {
        if (!$group->isExpertPanel) {
            throw ValidationException::withMessages(['group' => 'Genes can only be added to an Expert Panel.']);
        }

        if ($group->isVcep) {
            return $this->addGenesToVcep->handle($group, $genes);
        }
        if ($group->isGcep) {
            return $this->addGenesToGcep->handle($group, $genes);
        }
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
        $gtConn = config('database.gt_db_connection');
        $group = Group::findByUuidOrFail(request()->uuid);
        if ($group->isVcep) {
            return [
                'genes' => 'required|array|min:1',
                'genes.*' => 'required|array:hgnc_id,mondo_id',
                'genes.*.hgnc_id' => 'required|numeric|exists:'.$gtConn.'.genes,hgnc_id',
                'genes.*.mondo_id' => 'required|regex:/MONDO:\d\d\d\d\d\d\d/i|exists:'.$gtConn.'.diseases,mondo_id'
            ];
        }
        if ($group->isGcep) {
            return [
                'genes.*' => 'exists:'.$gtConn.'.genes,gene_symbol'
            ];
        }

        return [];
    }

    public function getValidationMessages(): array
    {
        $gtConn = config('database.gt_db_connection');
        $group = Group::findByUuidOrFail(request()->uuid);
        return [
            'required' => 'This field is required.',
            'exists' => 'Your selection is invalid.',
            'numeric' => 'Your selection is invalid.',
            'regex' => 'Your selection selection should have a mondo_id with the format "MONDO:#######".'
        ];
    }
    
}
