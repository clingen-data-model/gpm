<?php

namespace App\Modules\Group\Service;

use Illuminate\Support\Facades\Http;

class RemotePublicationClient
{
    public function fetch(string $source, string $id): array
    {
        return match ($source) {
            'pmid'  => $this->epmcBy("EXT_ID:$id") ?? $this->pubmedSummary($id) ?? [],
            'pmcid' => $this->epmcBy("EXT_ID:$id") ?? $this->epmcBy("PMCID:$id") ?? [],
            'doi'   => $this->fetchByDoi($id),
            'url'   => $this->fetchFromUrl($id),
            default => [],
        };
    }

    protected function fetchByDoi(string $doi): array
    {
        // 1) Europe PMC: EXT_ID
        $hit = $this->epmcBy("EXT_ID:$doi");
        if ($hit) return $hit;

        // 2) Europe PMC: DOI:
        $hit = $this->epmcBy("DOI:$doi");
        if ($hit) return $hit;

        // 3) PubMed: find PMID by DOI, then ESummary
        if ($pmid = $this->pubmedFindPmidByDoi($doi)) {
            if ($sum = $this->pubmedSummary($pmid)) return $sum;
        }

        // 4) Crossref fallback
        if ($cr = $this->crossref($doi)) return $cr;

        return [];
    }

    protected function epmcBy(string $query): ?array
    {
        $r = Http::timeout(10)->get(
            'https://www.ebi.ac.uk/europepmc/webservices/rest/search',
            ['query' => $query, 'format' => 'json']
        );
        if (!$r->ok()) return null;
        return collect($r->json('resultList.result') ?? [])->first();
    }

    protected function pubmedFindPmidByDoi(string $doi): ?string
    {
        $r = Http::timeout(10)->get('https://eutils.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi', [
            'db' => 'pubmed',
            'retmode' => 'json',
            'term' => $doi.'[DOI]',
        ]);
        if (!$r->ok()) return null;
        $ids = $r->json('esearchresult.idlist') ?? [];
        return $ids[0] ?? null;
    }

    protected function pubmedSummary(string $pmid): ?array
    {
        $r = Http::timeout(10)->get('https://eutils.ncbi.nlm.nih.gov/entrez/eutils/esummary.fcgi', [
            'db' => 'pubmed', 'retmode' => 'json', 'id' => $pmid,
        ]);
        if (!$r->ok()) return null;
        $s = $r->json("result.$pmid");
        if (!$s) return null;

        // map to a EuropePMC-ish shape so downstream code works
        return [
            'pmid' => $pmid,
            'title' => $s['title'] ?? null,
            'journalTitle' => $s['fulljournalname'] ?? null,
            'pubType' => ($s['pubtype'] ?? [])[0] ?? null,
            'firstPublicationDate' => $s['pubdate'] ?? null,
            'doi' => $s['elocationid'] ?? null, // sometimes contains DOI; not always
        ];
    }

    protected function crossref(string $doi): ?array
    {
        $r = Http::timeout(10)->get('https://api.crossref.org/works/'.rawurlencode($doi));
        if (!$r->ok()) return null;
        $m = $r->json('message') ?? [];

        // flatten Crossref -> our common shape
        $issued = $m['issued']['date-parts'][0] ?? null;
        $date = $issued ? sprintf('%04d-%02d-%02d', $issued[0] ?? 0, ($issued[1] ?? 1), ($issued[2] ?? 1)) : null;

        return [
            'title' => is_array($m['title'] ?? null) ? ($m['title'][0] ?? null) : ($m['title'] ?? null),
            'journalTitle' => is_array($m['container-title'] ?? null) ? ($m['container-title'][0] ?? null) : ($m['container-title'] ?? null),
            'doi' => $m['DOI'] ?? $doi,
            'firstPublicationDate' => $date,
            'pubType' => $m['type'] ?? null,
            'fullTextUrlList' => [
                'fullTextUrl' => array_map(fn($l) => ['url' => $l['URL']], $m['link'] ?? []),
            ],
            'url' => 'https://doi.org/'.$doi,
        ];
    }

    protected function fetchFromUrl(string $url): array
    {
        if (preg_match('~10\.\d{4,9}/\S+~i', $url, $m)) return $this->fetchByDoi($m[0]);
        if (preg_match('~/pubmed/(\d+)~i', $url, $m))   return $this->pubmedSummary($m[1]) ?? [];
        if (preg_match('~/(PMC\d+)~i', $url, $m))        return $this->epmcBy("PMCID:{$m[1]}") ?? [];
        return $this->epmcBy("EXT_ID:$url") ?? [];
    }

    public function extractDate(array $meta): ?string
    {
        return $meta['firstPublicationDate'] ?? $meta['pubdate'] ?? null;
    }

    public function extractType(array $meta): ?string
    {
        return $meta['pubType'] ?? null;
    }

    public function extractUrl(array $m): ?string
    {
        if (!empty($m['pmid']))  return "https://pubmed.ncbi.nlm.nih.gov/{$m['pmid']}/";
        if (!empty($m['pmcid'])) return "https://www.ncbi.nlm.nih.gov/pmc/articles/".strtoupper($m['pmcid'])."/";
        if (!empty($m['doi']))   return "https://doi.org/".ltrim($m['doi']);
        if (!empty($m['fullTextUrlList']['fullTextUrl'])) {
            foreach ($m['fullTextUrlList']['fullTextUrl'] as $u) {
                if (!empty($u['url'])) return $u['url'];
            }
        }
        if (!empty($m['id']) && !empty($m['source'])) return "https://europepmc.org/article/{$m['source']}/{$m['id']}";
        return null;
    }
}
