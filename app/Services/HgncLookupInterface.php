<?php
namespace App\Services;

interface HgncLookupInterface
{
    public function findSymbolById($hgncId): string;

    public function findHgncIdBySymbol($geneSymbol): int;
}
