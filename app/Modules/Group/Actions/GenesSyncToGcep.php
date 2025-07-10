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

    public function handle(Group $group, $genes): Group
    {
        $genes = collect($genes)->filter();

        if (!$group->isGcep) {
            throw new InvalidArgumentException('Expected group '.$group->name.' ('.$group->id.') to be a GCEP.  group is a '.$group->fullType->name);
        }

        $existingGeneSymbols = $group->expertPanel->genes->pluck('gene_symbol');
        $this->removeGenes($group, $existingGeneSymbols->diff($genes));
        $this->addNewGenes($group, $genes->diff($existingGeneSymbols));
        
        return $group;
    }

    private function removeGenes($group, $removedGeneSymbols)
    {
        if ($removedGeneSymbols->count() > 0) {
            $group->expertPanel->genes()
                ->whereIn('gene_symbol', $removedGeneSymbols)
                ->get()
                ->each(function ($g) use ($group) {
                    $g->delete();
                    event(new GeneRemoved($group, $g));
                });
        }
    }
    

    private function addNewGenes($group, $addedGeneSymbols)
    {
        if ($addedGeneSymbols->count() > 0) {
            
            $genes = collect();

            foreach ($addedGeneSymbols as $index => $geneSymbol) {
                try {
                    $hgncId = $this->hgncLookup->findHgncIdBySymbol($geneSymbol);
                } catch (\Throwable $e) {
                    throw ValidationException::withMessages([
                        "genes.$index" => "Gene symbol '{$geneSymbol}' not found or invalid.",
                    ]);
                }

                $genes->push(new Gene([
                    'hgnc_id' => $hgncId,
                    'gene_symbol' => $geneSymbol,
                ]));
            }
            if ($genes->isEmpty()) {
                throw new ValidationException('No valid genes provided for addition.');
            }

            $group->expertPanel->genes()->saveMany($genes);
            event(new GenesAdded($group, $genes));
        }
    }
}
