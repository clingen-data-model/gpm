<?php
namespace App\Modules\Group\Actions;

use InvalidArgumentException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Modules\Group\Models\Group;
use App\Services\HgncLookupInterface;
use App\Modules\ExpertPanel\Models\Gene;
use App\Modules\Group\Events\GenesAdded;
use App\Modules\Group\Events\GeneRemoved;
use Illuminate\Validation\ValidationException;

class GenesSyncToGcep
{
    public function __construct(private HgncLookupInterface $hgncLookup)
    {
    }

    public function handle(Group $group, $gene): Group
    {
        if (!$group->isGcep) {
            throw new InvalidArgumentException('Expected group '.$group->name.' ('.$group->id.') to be a GCEP.  group is a '.$group->fullType->name);
        }

        $model = new Gene([
            'hgnc_id'     => (int)($gene['hgnc_id'] ?? 0),
            'gene_symbol' => $gene['gene_symbol'] ?? null,
        ]);

        $group->expertPanel->genes()->save($model);
        event(new GenesAdded($group, collect($model)));
        
        return $group;
    }
}
