<?php
namespace App\Modules\Group\Actions;

use InvalidArgumentException;
use Illuminate\Support\Collection;
use App\Modules\Group\Models\Group;
use App\Actions\Services\HgncLookup;
use App\Modules\ExpertPanel\Models\Gene;
use App\Modules\Group\Events\GenesAdded;
use Illuminate\Validation\ValidationException;

class GenesAddToGcep
{
    public function __construct(private HgncLookup $hgncLookup)
    {
    }

    public function handle(Group $group, $genes): Group
    {
        if (!$group->isGcep) {
            throw new InvalidArgumentException('Expected group '.$group->name.' ('.$group->id.') to be a GCEP.  group is a '.$group->fullType->name);
        }

        foreach ($genes as $gene) {
            $group->expertPanel->genes()
                ->create([
                    'hgnc_id' => $gene['hgnc_id'],
                    'mondo_id' => null,
                    'gene_symbol' => $this->hgncLookup->findSymbolById($gene['hgnc_id'])
                ]);
        }

        event(new GenesAdded($group, $genes));
        return $group;
        
        return $group;
    }
}
