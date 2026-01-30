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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use App\Modules\ExpertPanel\Models\ScopeGeneSnapshot;

class GenesAddToVcep
{
    use AsAction;
    public function __construct()
    {
    }
    
    public function handle(Group $group, $genes): Group
    {
        if (!$group->isVcepOrScvcep) {
            throw ValidationException::withMessages(['group' => 'The group is not a VCEP.']);
        }

        $items = collect($genes);
        if ($items->isEmpty()) {
            throw ValidationException::withMessages(['genes' => 'Please provide at least one gene.']);
        }

        $errors = $models = $snapshotPayloads = [];

        foreach ($items as $idx => $gene) {
            $hgncId     = $gene['hgnc_id']    ?? null;
            $geneSymbol = $gene['gene_symbol'] ?? null;

            if (!is_numeric($hgncId)) {
                $errors["genes.$idx.hgnc_id"] = 'Gene must have an HGNC ID.';
            }
            if (!$geneSymbol) {
                $errors["genes.$idx.gene_symbol"] = 'Gene must have a gene symbol.';
            }

            $models[] = new Gene([
                'hgnc_id'       => (int) $hgncId,
                'gene_symbol'   => $geneSymbol,
                'disease_name'  => $gene['disease_name']  ?? null,
                'mondo_id'      => $gene['mondo_id']      ?? null,
                'moi'           => $gene['moi']           ?? null,
                'date_approved' => $gene['date_approved'] ?? null,
                'plan'          => $gene['plan'] ?? null,
                'gt_curation_uuid'   => data_get($gene, 'plan.curation_id') ?? null,
            ]);
            $snapshotPayloads[] = $gene['plan'] ?? null;
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        DB::transaction(function () use ($group, &$models, $snapshotPayloads) {
            $savedModels = collect($group->expertPanel->genes()->saveMany($models))->values();    
        });
        
        event(new GenesAdded($group, collect($models)));       

        return $group;
    }
}
