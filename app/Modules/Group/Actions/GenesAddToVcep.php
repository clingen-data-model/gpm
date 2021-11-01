<?php

namespace App\Modules\Group\Actions;

use Illuminate\Http\Request;
use App\Modules\Group\Models\Group;
use App\Actions\Services\HgncLookup;
use Illuminate\Support\Facades\Auth;
use Lorisleiva\Actions\ActionRequest;
use App\Modules\ExpertPanel\Models\Gene;
use App\Modules\Group\Events\GenesAdded;
use Illuminate\Support\Facades\Validator;
use Lorisleiva\Actions\Concerns\AsAction;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;

class GenesAddToVcep
{
    use AsAction;
    public function __construct(private HgncLookup $hgncLookup)
    {
    }



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
                    'gene_symbol' => $this->hgncLookup->findSymbolById($gene['hgnc_id'])
                ]);
        }

        event(new GenesAdded($group, $genes));
        return $group;
    }
}
