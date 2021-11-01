<?php
namespace App\Actions\Services;

class HgncLookup
{
    public function findSymbolById($hgncId): string
    {
        // NOTE: stubbed in so we don't get sidetracked figuring out where info comes from.
        return uniqid();
    }

    public function findHgncIdBySymbol($geneSymbol): int
    {
        // NOTE: stubbed in so we don't get sidetracked figuring out where info comes from.
        $rng = range(1, 99999);
        return $rng[array_rand($rng)];
    }
}
