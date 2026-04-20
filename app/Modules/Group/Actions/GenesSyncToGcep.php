<?php
namespace App\Modules\Group\Actions;

use InvalidArgumentException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Modules\Group\Models\Group;
use App\Modules\ExpertPanel\Models\Gene;
use App\Modules\Group\Events\GenesAdded;
use App\Modules\Group\Events\GeneRemoved;
use Illuminate\Validation\ValidationException;

class GenesSyncToGcep
{
    public function __construct()
    {
    }

    public function handle(Group $group, $genes): Group
    {
        if (!$group->isGcep) {
            throw new InvalidArgumentException('Expected group '.$group->name.' ('.$group->id.') to be a GCEP.  group is a '.$group->fullType->name);
        }

        $items = collect($genes)->filter();
        if ($items->isEmpty()) {
            throw ValidationException::withMessages(['genes' => 'Please provide at least one gene.']);
        }

        foreach ($items as $idx => $gene) {
            $hgncId    = (int)($gene['hgnc_id'] ?? 0);
            $geneSymbol = $gene['gene_symbol'] ?? null;

            if ($hgncId <= 0) {
                $errors["genes.$idx.hgnc_id"] = 'Gene must have an HGNC ID.';
            }
            if (!$geneSymbol) {
                $errors["genes.$idx.gene_symbol"] = 'Gene must have a gene symbol.';
            }
            $models[] = new Gene([
                'hgnc_id'     => $hgncId,
                'gene_symbol' => $geneSymbol,
            ]);
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        DB::transaction(function () use ($group, &$models) {
            $group->expertPanel->genes()->saveMany($models);
        });

        event(new GenesAdded($group, collect($models)));
        
        return $group;
    }
}
