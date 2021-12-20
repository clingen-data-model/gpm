<?php
namespace App\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use App\Services\HgncLookupInterface;

class HgncLookup implements HgncLookupInterface
{
    public function findSymbolById($hgncId): string
    {
        $geneData = DB::connection(config('database.gt_db_connection'))
                ->table('genes')
                ->select('gene_symbol')
                ->where('hgnc_id', $hgncId)
                ->first();
        if (!$geneData) {
            throw new Exception('No gene with HGNC ID '.$hgncId.' in our records.', 404);
        }
        return $geneData->gene_symbol;
    }

    public function findHgncIdBySymbol($geneSymbol): int
    {
        $geneData = DB::connection(config('database.gt_db_connection'))
                ->table('genes')
                ->select('hgnc_id')
                ->where('gene_symbol', $geneSymbol)
                ->first();
        if (!$geneData) {
            throw new Exception('No gene with gene symbol '.$geneSymbol.' in our records.', 404);
        }
        return $geneData->hgnc_id;
    }
}
