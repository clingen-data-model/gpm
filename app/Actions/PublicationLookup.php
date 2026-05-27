<?php

namespace App\Actions;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Support\Facades\Log;

class PublicationLookup
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate(['query' => ['required', 'string', 'max:500']]);
        return response()->json($this->handle($validated['query']));
    }

    public function handle(string $raw): array
    {
        $lookup = $this->detectIdentifier($raw);
        if (! $lookup) {
            throw ValidationException::withMessages(['query' => 'Please enter a DOI, PMID, or PMCID.']);
        }
        $publicationApi = rtrim((string) config('services.publication.base_url'), '/');
        $response = Http::acceptJson()->timeout(15)->get($publicationApi, [
                        'query' => $lookup['query'],
                        'format' => 'json',
                        'pageSize' => 1,
                        'resultType' => 'core',
                    ]);
        if (! $response->successful()) {
            throw new HttpException(502, 'Publication API lookup failed.');
        }
        $results = data_get($response->json(), 'resultList.result', []);
        if (empty($results)) {
            throw ValidationException::withMessages(['query' => 'No publication found.']);
        }
        return $this->normalizeResponse($results[0], $lookup);
    }

    private function stripDoiUrl(string $value): string
    {
        return preg_replace('#^https?://doi\.org/#i', '', $value);
    }

    private function detectIdentifier(string $raw): ?array
    {
        $value = trim($raw);
        if ($value === '') { return null; }

        if (preg_match('#^https?://doi\.org/10\.\S+$#i', $value)) {
            $doi = $this->stripDoiUrl($value);
            return ['type' => 'doi', 'normalized' => $doi, 'query' => 'DOI:"'.$doi.'"'];
        }
        if (preg_match('#^doi:\s*10\.\S+$#i', $value)) {
            $doi = preg_replace('#^doi:\s*#i', '', $value);
            return ['type' => 'doi', 'normalized' => $doi, 'query' => 'DOI:"'.$doi.'"'];
        }
        if (preg_match('#^10\.\S+$#i', $value)) {
            return ['type' => 'doi', 'normalized' => $value, 'query' => 'DOI:"'.$value.'"'];
        }
        if (preg_match('#^https?://pubmed\.ncbi\.nlm\.nih\.gov/(\d+)/?$#i', $value, $m)) {
            return ['type' => 'pmid', 'normalized' => $m[1], 'query' => 'EXT_ID:'.$m[1].' AND SRC:MED'];
        }
        if (preg_match('#^pmid:\s*(\d+)$#i', $value, $m)) {
            return ['type' => 'pmid', 'normalized' => $m[1], 'query' => 'EXT_ID:'.$m[1].' AND SRC:MED'];
        }
        if (preg_match('#^\d+$#', $value)) {
            return ['type' => 'pmid', 'normalized' => $value, 'query' => 'EXT_ID:'.$value.' AND SRC:MED'];
        }
        if (preg_match('#^https?://pmc\.ncbi\.nlm\.nih\.gov/articles/(PMC\d+)/?$#i', $value, $m)) {
            $pmcid = strtoupper($m[1]);
            return ['type' => 'pmcid', 'normalized' => $pmcid, 'query' => 'PMCID:'.$pmcid];
        }
        if (preg_match('#^pmcid:\s*(PMC\d+)$#i', $value, $m)) {
            $pmcid = strtoupper($m[1]);
            return ['type' => 'pmcid', 'normalized' => $pmcid, 'query' => 'PMCID:'.$pmcid];
        }
        if (preg_match('#^(PMC\d+)$#i', $value, $m)) {
            $pmcid = strtoupper($m[1]);
            return ['type' => 'pmcid', 'normalized' => $pmcid, 'query' => 'PMCID:'.$pmcid];
        }
        return null;
    }

    private function preferredLink(array $meta, string $source): ?string
    {
        return data_get($meta, "{$source}.link") ?? data_get($meta, 'pmcid.link') ?? data_get($meta, 'pmid.link') ?? data_get($meta, 'doi.link');
    }

    private function buildPublicationMeta(array $work): array
    {
        $doi = $work['doi'] ?? null;
        $pmid = $work['pmid'] ?? null;
        $pmcid = isset($work['pmcid']) ? strtoupper($work['pmcid']) : null;

        $authors = collect(data_get($work, 'authorList.author', []))->map(fn ($a) => $a['fullName'] ?? $a['collectiveName'] ?? null)->filter()->values()->all();

        if (empty($authors) && ! empty($work['authorString'])) {
            $authors = collect(explode(',', rtrim($work['authorString'], '.')))->map(fn ($author) => trim($author))->filter()->values()->all();
        }

        return [
            'title' => $work['title'] ?? '',
            'type' => implode(', ', data_get($work, 'pubTypeList.pubType', [])) ?? null,
            'published_at' => $work['firstPublicationDate'] ?? $work['electronicPublicationDate'] ?? $work['printPublicationDate'] ?? null,
            'journal' => data_get($work, 'journalInfo.journal.title') ?? '',
            'doi' => [
                'id' => $doi ? $this->stripDoiUrl($doi) : null,
                'link' => $doi ? 'https://doi.org/'.$this->stripDoiUrl($doi) : null,
            ],
            'pmid' => [
                'id' => $pmid,
                'link' => $pmid ? 'https://pubmed.ncbi.nlm.nih.gov/'.$pmid.'/' : null,
            ],
            'pmcid' => [
                'id' => $pmcid,
                'link' => $pmcid ? 'https://pmc.ncbi.nlm.nih.gov/articles/'.$pmcid.'/' : null,
            ],
            'authors' => $authors,
        ];
    }

    private function normalizeResponse(array $work, array $lookup): array
    {
        $meta = $this->buildPublicationMeta($work);
        $source = $lookup['type'];
        $identifier = data_get($meta, "{$source}.id") ?? $lookup['normalized'];
        return [
            'source' => $source,
            'identifier' => $identifier,
            'link' => $this->preferredLink($meta, $source),
            'pub_type' => $meta['type'],
            'published_at' => $meta['published_at'],
            'meta' => $meta,
        ];
    }
}