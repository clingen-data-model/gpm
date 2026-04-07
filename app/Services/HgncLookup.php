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
        try {
            $response = $this->gtApiService->getGeneSymbolById((int)$hgncId);

            if (!($response['success'] ?? false) || empty($response['data']['gene_symbol'])) {
                throw new Exception('No gene with HGNC ID ' . $hgncId . ' in our records.', 404);
            }

            return $response['data']['gene_symbol'];
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve gene data.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function findHgncIdBySymbol($geneSymbol): int
    {
        try {
            $response = $this->gtApiService->getGeneSymbolBySymbol((string)$geneSymbol);

            if (!($response['success'] ?? false) || empty($response['data']['hgnc_id'])) {
                throw new Exception('No gene with gene symbol ' . $geneSymbol . ' in our records.', 404);
            }

            return (int)$response['data']['hgnc_id'];
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve gene data.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
}
