<?php
namespace App\Services;

use Exception;
use App\Services\HgncLookupInterface;
use App\Services\Api\GtApiService;

class HgncLookup implements HgncLookupInterface
{
    protected GtApiService $gtApiService;

    public function __construct(GtApiService $gtApiService)
    {
        $this->gtApiService = $gtApiService;
    }

    public function findSymbolById($hgncId): string
    { 
        return $this->gtApiService->getGeneSymbolById((int)$hgncId);
    }

    public function findHgncIdBySymbol($geneSymbol): int
    {
        return $this->gtApiService->getGeneSymbolBySymbol((string)$geneSymbol);
    }
}