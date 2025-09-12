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

    public function handle(Group $group, $gene): Group
    {
        if (!$group->isExpertPanel) {
            throw ValidationException::withMessages(['group' => 'Gene can only be added to an Expert Panel.']);
        }

        if ($group->isVcepOrScvcep) {
            return $this->addGenesToVcep->handle($group, $gene);
        }
        if ($group->isGcep) {
            return $this->addGenesToGcep->handle($group, $gene);
        }
        return $group;
    }

    public function asController(ActionRequest $request, Group $group)
    {
        $this->handle($group, $request->gene);
        return $group->fresh()->load('expertPanel.genes');
    }

    public function authorize(ActionRequest $request): bool
    {
        return Auth::user()->can('addGene', $request->group);
    }
    
    public function rules(ActionRequest $request): array
    {
        $panelId = optional($request->group->expertPanel)->id;
        $table   = 'genes';

        $rules = [
            'gene'              => 'required',
            'gene.hgnc_id'      => ['required', 'integer'],
            'gene.gene_symbol'  => 'required|string',
            'gene.mondo_id'     => 'nullable|regex:/MONDO:\d{7}/i',
            'gene.moi'          => ['nullable', 'string'],
        ];


        if ($request->group?->isGcep) {
            // GCEP: enforce uniqueness on hgnc_id only
            $rules['gene.hgnc_id'][] = Rule::unique($table, 'hgnc_id')->where(fn($q) => $q->where('expert_panel_id', $panelId)->whereNull('deleted_at'));
        } elseif ($request->group?->isVcepOrScvcep) {
            // VCEP: require mondo_id + moi and enforce composite uniqueness
            $rules['gene.hgnc_id'][] = Rule::unique($table, 'hgnc_id')
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
            'gene.required'             => 'Please provide a gene.',
            'gene.hgnc_id.required'     => 'Gene must have an HGNC ID.',
            'gene.hgnc_id.integer'      => 'The HGNC ID must be an integer.',
            'gene.hgnc_id.unique'       => 'This gene is already on this Expert Panel.',
            'gene.gene_symbol.required' => 'Gene must have a gene symbol.',
            'gene.gene_symbol.string'   => 'The Gene Symbol must be a string.',
            'gene.mondo_id.regex'       => 'The MONDO ID must follow the format "MONDO:#######".',
        ];
    }
}
