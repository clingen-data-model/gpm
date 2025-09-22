<?php

namespace App\Modules\Group\Service;

use Illuminate\Support\Facades\Http;

class RemotePublicationClient
{
    public function fetch(string $source, string $id): array
    {
        // Europe PMC as a first pass; flesh this out next step.
        // https://www.ebi.ac.uk/europepmc/webservices/rest/search?query=EXT_ID:{id}&format=json
        $resp = Http::timeout(8)->get('https://www.ebi.ac.uk/europepmc/webservices/rest/search', [
            'query'  => "EXT_ID:{$id}",
            'format' => 'json',
        ])->throw();

        return collect($resp->json('resultList.result') ?? [])->first() ?? [];
    }

    public function extractDate(array $meta): ?string
    {
        return $meta['firstPublicationDate'] ?? $meta['pubdate'] ?? null;
    }

    public function extractType(array $meta): ?string
    {
        $t = strtolower($meta['pubType'] ?? '');
        return $t === 'preprint' ? 'preprint' : ($t ?: 'published');
    }
}
