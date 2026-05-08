<?php

namespace App\Actions;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PublicationLookupOpenAlex
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'query' => ['required', 'string', 'max:500'],
        ]);

        return response()->json(
            $this->handle($validated['query'])
        );
    }

    public function handle(string $raw): array
    {
        $lookup = $this->detectIdentifier($raw);
        if (! $lookup) {
            throw ValidationException::withMessages(['query' => 'Please enter a DOI, PMID, or PMCID.']);
        }

        $apiKey = config('services.openalex.api_key');
        $baseUrl = rtrim((string) config('services.openalex.base_url'), '/');

        if (! $apiKey) {
            throw new HttpException(500, 'OpenAlex API key is not configured.');
        }
        $response = Http::acceptJson()->timeout(15)->get($baseUrl.'/works/'.$lookup['path'], ['api_key' => $apiKey]);
        if ($response->status() === 404) {
            throw ValidationException::withMessages(['query' => 'No publication found.']);
        }
        if (! $response->successful()) {
            throw new HttpException(502, 'OpenAlex lookup failed.');
        }
        return $this->normalizeOpenAlexWork($response->json(), $lookup);
    }

    private function stripDoiUrl(string $value): string
    {
        return preg_replace('#^https?://doi\.org/#i', '', $value);
    }

    private function tailId(?string $value): string
    {
        return preg_replace('#^.*/#', '', (string) $value);
    }

    private function detectIdentifier(string $raw): ?array
    {
        $value = trim($raw);

        if ($value === '') { return null; }
        if (preg_match('#^https?://doi\.org/10\.\S+$#i', $value)) {
            $doi = $this->stripDoiUrl($value);
            return ['type' => 'doi', 'normalized' => $doi, 'path' => 'doi:'.$doi];
        }
        if (preg_match('#^doi:\s*10\.\S+$#i', $value)) {
            $doi = preg_replace('#^doi:\s*#i', '', $value);
            return ['type' => 'doi', 'normalized' => $doi, 'path' => 'doi:'.$doi];
        }
        if (preg_match('#^10\.\S+$#i', $value)) {
            return ['type' => 'doi', 'normalized' => $value, 'path' => 'doi:'.$value];
        }
        if (preg_match('#^https?://pubmed\.ncbi\.nlm\.nih\.gov/(\d+)/?$#i', $value, $m)) {
            return ['type' => 'pmid', 'normalized' => $m[1], 'path' => 'pmid:'.$m[1]];
        }
        if (preg_match('#^pmid:\s*(\d+)$#i', $value, $m)) {
            return ['type' => 'pmid', 'normalized' => $m[1], 'path' => 'pmid:'.$m[1]];
        }
        if (preg_match('#^\d+$#', $value)) {
            return ['type' => 'pmid', 'normalized' => $value, 'path' => 'pmid:'.$value];
        }
        if (preg_match('#^https?://pmc\.ncbi\.nlm\.nih\.gov/articles/(PMC\d+)/?$#i', $value, $m)) {
            $pmcid = strtoupper($m[1]);
            return ['type' => 'pmcid', 'normalized' => $pmcid, 'path' => 'pmcid:'.$pmcid];
        }
        if (preg_match('#^pmcid:\s*(PMC\d+)$#i', $value, $m)) {
            $pmcid = strtoupper($m[1]);
            return ['type' => 'pmcid', 'normalized' => $pmcid, 'path' => 'pmcid:'.$pmcid];
        }
        if (preg_match('#^(PMC\d+)$#i', $value, $m)) {
            $pmcid = strtoupper($m[1]);
            return ['type' => 'pmcid', 'normalized' => $pmcid, 'path' => 'pmcid:'.$pmcid];
        }

        return null;
    }

    private function preferredLink(array $work, string $source): ?string
    {
        return data_get($work, "ids.$source")
            ?? data_get($work, 'primary_location.landing_page_url');
    }

    private function buildPublicationMeta(array $work): array
    {
        $doi = data_get($work, 'ids.doi');
        $pmid = data_get($work, 'ids.pmid');
        $pmcid = data_get($work, 'ids.pmcid');

        return [
            'title' => $work['display_name'] ?? '',
            'type' => $work['type'] ?? null,
            'published_at' => $work['publication_date'] ?? null,
            'journal' => data_get($work, 'primary_location.source.display_name', ''),
            'doi' => [
                'id' => $doi ? $this->stripDoiUrl($doi) : null,
                'link' => $doi,
            ],
            'pmid' => [
                'id' => $pmid ? $this->tailId($pmid) : null,
                'link' => $pmid,
            ],
            'pmcid' => [
                'id' => $pmcid ? strtoupper($this->tailId($pmcid)) : null,
                'link' => $pmcid,
            ],
            'authors' => collect($work['authorships'] ?? [])
                ->map(fn ($a) => data_get($a, 'author.display_name'))
                ->filter()
                ->values()
                ->all(),
        ];
    }

    private function normalizeOpenAlexWork(array $work, array $lookup): array
    {
        $meta = $this->buildPublicationMeta($work);
        $source = $lookup['type'];

        $identifier = data_get($meta, "{$source}.id") ?? $lookup['normalized'];

        return [
            'source' => $source,
            'identifier' => $identifier,
            'link' => $this->preferredLink($work, $source),
            'pub_type' => $meta['type'],
            'published_at' => $meta['published_at'],
            'meta' => $meta,
        ];
    }
}