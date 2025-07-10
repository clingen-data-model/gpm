<?php
namespace App\Modules\Group\Actions;

use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\Auth;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsObject;
use App\Modules\Group\Actions\GenesSyncToGcep;
use App\Modules\Group\Actions\GenesAddToVcep;
use Lorisleiva\Actions\Concerns\AsController;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;

class GenesAdd
{
    use AsController;
    use AsObject;

    public function __construct(private GenesAddToVcep $addGenesToVcep, private GenesSyncToGcep $addGenesToGcep)
    {
    }

    public function handle(Group $group, $genes): Group
    {
        if (!$group->isExpertPanel) {
            throw ValidationException::withMessages(['group' => 'Genes can only be added to an Expert Panel.']);
        }

        if ($group->isVcepOrScvcep) {
            return $this->addGenesToVcep->handle($group, $genes);
        }
        if ($group->isGcep) {
            return $this->addGenesToGcep->handle($group, $genes);
        }
        return $group;
    }

    public function asController(ActionRequest $request, Group $group)
    {
        $this->handle($group, $request->genes);
        return $group->fresh()->load('expertPanel.genes');
    }

    public function authorize(ActionRequest $request): bool
    {
        return Auth::user()->can('addGene', $request->group);
    }
    
    public function rules(ActionRequest $request): array
    {        
        $group = $request->group;
        if ($group->isVcepOrScvcep) {
            return [
                'genes' => 'required|array|min:1',
                'genes.*' => 'required|array:hgnc_id,mondo_id',
                'genes.*.hgnc_id' => 'required|numeric',
                'genes.*.mondo_id' => 'required|regex:/MONDO:\d{7}/i'
            ];
        }
        if ($group->isGcep) {
            return [
                'genes' => 'required|array|min:1',
                'genes.*' => 'required|string'
            ];
        }

        return [];
    }

    public function getValidationMessages(): array
    {
        return [
            'required' => 'This field is required.',
            'exists' => 'Your selection is invalid.',
            'numeric' => 'Your selection is invalid.',
            'regex' => 'Your selection selection should have a mondo_id with the format "MONDO:#######".'
        ];
    }
}
