<?php
namespace App\Services;

use Exception;
use App\Services\HgncLookupInterface;
use App\Services\Api\GtApiService;

class HgncLookup implements HgncLookupInterface
{
    protected GtApiService $gtApi;

    public function __construct(GtApiService $gtApi)
    {
        $this->gtApi = $gtApi;
    }

    public function findSymbolById($hgncId): string
    { 
        $result = $this->gtApi->getGeneSymbolById((int) $hgncId);
        return $result['gene_symbol'] ?? '';
    }

    public function findHgncIdBySymbol($geneSymbol): int
    {
        $result = $this->gtApi->getGeneSymbolBySymbol((string)$geneSymbol);
        return $result['hgnc_id'] ?? null;
    }
}