<?php
namespace App\Actions;

use Illuminate\Support\Facades\DB;
use App\Models\GeneTracker\Disease;
use App\Modules\Group\Actions\GenesAdd;
use App\Modules\ExpertPanel\Models\Gene;
use App\Models\GeneTracker\Gene as GtGene;
use Lorisleiva\Actions\Concerns\AsCommand;
use App\Modules\ExpertPanel\Models\ExpertPanel;

class ImportScopeFromCspec
{
    use AsCommand;

    public $commandSignature = "dev:import-cspec-scope";

    public function __construct(private GenesAdd $addGene)
    {
        //code
    }
    

    public function handle()
    {
        $index = $this->getJsonFor('https://cspec.genome.network/cspec/SequenceVariantInterpretation/id/', '/tmp/cspec_svi_index.json');

        $affToEp = $this->affiliationEpMap();

        foreach ($index->data as $svi) {
            $sviContent = $this->getJsonFor($svi->ldhIri, '/tmp/cspec_svi_'.$svi->entId.'.json');
            if (
                !isset($sviContent->data->ld)
                || !isset($sviContent->data->ld->Gene)
                || !isset($sviContent->data->ldFor)
                || !isset($sviContent->data->ldFor->Organization)
            ) {
                continue;
            }
            $hgncId = $this->getHgncId($sviContent->data->ld->Gene[0]->entContent->HGNC);
            $mondoId = $this->getMondoId($sviContent->data->ld->Disease[0]->entContent->MONDO);
            $affiliation = $sviContent->data->ldFor->Organization[0];

            $ep = ExpertPanel::findByAffiliationId($affiliation->entId);
            if (!$ep) {
                dump('NO EP FOUND FOR AFFILIATION ID '.$affiliation->entId);
                break;
            }
            
            $gtGene = GtGene::query()
                        ->where('hgnc_id', $hgncId)
                        ->first();
            if (!$gtGene) {
                dump('NO GENE FOUND FOR HGNC_ID '.$hgncId);
                break;
            }

            $gtDisease = Disease::where('mondo_id', $mondoId)->first();
            if (!$gtDisease) {
                dump('NO DISEASE FOUND FOR MONDO_ID '.$mondoId);
                break;
            }

            Gene::firstOrCreate([
                'expert_panel_id' => $ep->id,
                'hgnc_id' => $hgncId,
                'mondo_id' => $mondoId,
            ], [
                // 'hgnc_id' => $hgncId,
                // 'expert_panel_id' => $ep->id,
                'gene_symbol' => $gtGene->omim_id,
                // 'mondo_id' => $mondoId,
                'disease_name' => $gtDisease->name
            ]);
        }
    }

    private function getMondoId($diseaseObj)
    {
        $urlParts = explode('/', $diseaseObj->id);
        return str_replace('_', ':', $urlParts[count($urlParts)-1]);
    }
    
    private function getHgncId($hgncObj)
    {
        [$mondo, $hgncId] = explode(':', $hgncObj->agr);
        return $hgncId;
    }
    

    private function getJsonFor($url, $cachePath)
    {
        // dump('getJsonFor: '.$url.' -> '.$cachePath);
        if (!file_exists($cachePath)) {
            // dump('not found in the cache. Download.');
            $json = file_get_contents($url);
            file_put_contents($cachePath, $json);
        }
        return json_decode(file_get_contents($cachePath));
    }
    

    private function affiliationEpMap()
    {
        return ExpertPanel::query()
                ->select('affiliation_id', 'id')
                ->get()
                ->keyBy('affiliation_id')
                ->pluck('id');
    }
}
