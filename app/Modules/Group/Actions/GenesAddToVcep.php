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
        
        $genes = collect(array_map(function ($gene) {
            // $gene ["hgnc_id" => 11335, "mondo_id" => "MONDO:0015655"]
            return new Gene([
                'hgnc_id' => $gene['hgnc_id'],
                'gene_symbol' => $this->hgncLookup->findSymbolById($gene['hgnc_id']) ?? null,
                'mondo_id' => $gene['mondo_id'],
                'disease_name' => $this->mondoLookup->findNameByOntologyId($gene['mondo_id']) ?? null,
            ]);
        }, $genes));

        $group->expertPanel->genes()->saveMany($genes);
        event(new GenesAdded($group, $genes));
        
        return $group;
    }
}
