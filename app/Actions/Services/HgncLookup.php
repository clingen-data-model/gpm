<?php
namespace App\Actions\Services;

class HgncLookup
{
    public function findSymbolById($hgncId): string
    {
        return uniqid();
    }
}
