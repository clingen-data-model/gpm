<?php

namespace App\Modules\Group\Actions;

use App\Modules\ExpertPanel\Models\Gene;
use App\Modules\Group\Events\GenesAdded;
use App\Modules\Group\Models\Group;
use App\Services\HgncLookupInterface;
use App\Services\MondoLookupInterface;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;

class GenesAddToVcep
{
    use AsAction;

    public function __construct(private HgncLookupInterface $hgncLookup, private MondoLookupInterface $mondoLookup)
    {
    }

    public function handle(Group $group, array $genes): Group
    {
        if (! $group->isVcep) {
            throw ValidationException::withMessages(['group' => 'The group is not a VCEP.']);
        }

        $genes = collect(array_map(function ($gene) {
            return new Gene([
                'hgnc_id' => $gene['hgnc_id'],
                'gene_symbol' => $this->hgncLookup->findSymbolById($gene['hgnc_id']),
                'mondo_id' => $gene['mondo_id'],
                'disease_name' => $this->mondoLookup->findNameByMondoId($gene['mondo_id']),
            ]);
        }, $genes));

        $group->expertPanel->genes()->saveMany($genes);
        event(new GenesAdded($group, $genes));

        return $group;
    }
}
