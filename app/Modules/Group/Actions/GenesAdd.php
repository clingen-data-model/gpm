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
use Illuminate\Validation\Rule;

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
            throw ValidationException::withMessages(['group' => 'Gene can only be added to an Expert Panel.']);
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
        $panelId = optional($request->group->expertPanel)->id;
        $table   = 'scope_genes';

        $rules = [
            'genes'                 => ['required', 'array', 'min:1'],
            
            'genes.*.hgnc_id'       => ['required', 'integer'],
            'genes.*.gene_symbol'   => ['required', 'string'],
            'genes.*.mondo_id'      => ['nullable', 'regex:/MONDO:\d{7}/i'],
            'genes.*.moi'           => ['nullable', 'string'],
        ];


        if ($request->group?->isGcep) {
            // GCEP: enforce uniqueness on hgnc_id only
            $rules['genes.*.hgnc_id'][] = Rule::unique($table, 'hgnc_id')->where(fn($q) => $q->where('expert_panel_id', $panelId)->whereNull('deleted_at'));
        } elseif ($request->group?->isVcepOrScvcep) {
            // VCEP: require mondo_id + moi and enforce composite uniqueness
            $rules['genes.*.hgnc_id'][] = Rule::unique($table, 'hgnc_id')
                                        ->where(function ($q) use ($panelId, $request) {
                                            $q->where('expert_panel_id', $panelId)
                                            ->where('gene_symbol', $request->input('gene.gene_symbol'))
                                            ->when(
                                                filled($request->input('gene.mondo_id')),
                                                fn ($qq) => $qq->where('mondo_id', $request->input('gene.mondo_id')),
                                                fn ($qq) => $qq->whereNull('mondo_id')
                                            )
                                            ->when(
                                                filled($request->input('gene.moi')),
                                                fn ($qq) => $qq->where('moi', $request->input('gene.moi')),
                                                fn ($qq) => $qq->whereNull('moi')
                                            )
                                            ->whereNull('deleted_at');
                                        });
        }
        return $rules;
    }

    public function getValidationMessages(): array
    {
        return [
            'genes.required'                => 'Please provide a gene.',
            'genes.array'                   => 'The genes payload must be an array of gene objects.',
            'genes.min'                     => 'Please provide at least one gene.',
            'genes.*.hgnc_id.required'      => 'Gene must have an HGNC ID.',
            'genes.*.hgnc_id.integer'       => 'The HGNC ID must be an integer.',
            'genes.*.hgnc_id.unique'        => 'This gene is already on this Expert Panel.',
            'genes.*.gene_symbol.required'  => 'Gene must have a gene symbol.',
            'genes.*.gene_symbol.string'    => 'The Gene Symbol must be a string.',
            'genes.*.mondo_id.regex'        => 'The MONDO ID must follow the format "MONDO:#######".',
            'genes.*.moi.string'            => 'The Mode of Inheritance must be a string.',
        ];
    }
}
