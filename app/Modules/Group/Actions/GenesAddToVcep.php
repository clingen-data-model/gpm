<?php

namespace App\Modules\Group\Actions;

use Illuminate\Http\Request;
use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\Auth;
use App\Services\HgncLookupInterface;
use Lorisleiva\Actions\ActionRequest;
use App\Services\DiseaseLookupInterface;
use App\Modules\ExpertPanel\Models\Gene;
use App\Modules\Group\Events\GenesAdded;
use Illuminate\Support\Facades\Validator;
use Lorisleiva\Actions\Concerns\AsAction;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;

class GenesAddToVcep
{
    use AsAction;
    public function __construct(private HgncLookupInterface $hgncLookup, private DiseaseLookupInterface $mondoLookup)
    {
    }



    public function handle(Group $group, array $genes): Group
    {
        if (!$group->isVcepOrScvcep) {
            throw ValidationException::withMessages(['group' => 'The group is not a VCEP.']);
        }

        $genes = collect();

        foreach ($inputGenes as $index => $gene) {
            try {
                $geneSymbol = $this->hgncLookup->findSymbolById($gene['hgnc_id']);
            } catch (\Throwable $e) {
                throw ValidationException::withMessages([
                    "genes.$index.hgnc_id" => "HGNC ID {$gene['hgnc_id']} not found or invalid.",
                ]);
            }

            try {
                $diseaseName = $this->mondoLookup->findNameByOntologyId($gene['mondo_id']);
            } catch (\Throwable $e) {
                throw ValidationException::withMessages([
                    "genes.$index.mondo_id" => "MONDO ID {$gene['mondo_id']} not found or invalid.",
                ]);
            }

            $genes->push(new Gene([
                'hgnc_id' => $gene['hgnc_id'],
                'gene_symbol' => $geneSymbol,
                'mondo_id' => $gene['mondo_id'],
                'disease_name' => $diseaseName,
            ]));
        }
        if ($genes->isEmpty()) {
            throw new ValidationException('No valid genes provided for addition.');
        }

        $group->expertPanel->genes()->saveMany($genes);
        event(new GenesAdded($group, $genes));
        
        return $group;
    }
}
