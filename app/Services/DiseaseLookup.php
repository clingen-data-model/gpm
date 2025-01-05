<?php
namespace App\Services;

use App\Services\DiseaseLookupInterface;
use Exception;
use Illuminate\Support\Facades\DB;

class DiseaseLookup implements DiseaseLookupInterface
{
    const SUPPORTED_ONTOLOGIES = ['mondo', 'doid'];

    public function findNameByOntologyId(string $ontologyId): string
    {
        $ontology = strtolower(explode(':', $ontologyId)[0]);
        if (!in_array($ontology, static::SUPPORTED_ONTOLOGIES)) {
            throw new Exception('Ontology '.$ontology.' is not supported');
        }

        $diseaseData = DB::connection(config('database.gt_db_connection'))
                ->table('diseases')
                ->select('name')
                ->where($ontology.'_id', $ontologyId)
                ->first();

        if (!$diseaseData) {
            throw new Exception('We couldn\'t find a disease with '. $ontology .' ID '.$ontologyId.' in our records.');
        }

        return $diseaseData->name;
    }
}
