import { useFetch } from '@vueuse/core'

/**
 * RemotePublicationClient (frontend)
 *
 * Mirrors the PHP RemotePublicationClient using Vue patterns and a useFetch composable.
 * All methods return plain JS objects (mapped to a EuropePMC-ish shape) or null/empty objects on miss.
 */
export function useRemotePublicationClient() {
  const TIMEOUT = 10000

  const getJson = async (url, params) => {
    let requestUrl = url
    if (params && typeof params === 'object' && Object.keys(params).length) {
      const usp = new URLSearchParams()
      Object.entries(params).forEach(([k, v]) => {
        if (v !== undefined && v !== null) usp.append(k, String(v))
      })
      requestUrl += (url.includes('?') ? '&' : '?') + usp.toString()
    }
    const res = await useFetch(requestUrl, { timeout: TIMEOUT, immediate: true, headers: { Accept: 'application/json' } })
      .get()
      .json()
    if (res.error.value) throw res.error.value
    return res.data.value
  }

  const epmcBy = async (query) => {
    const json = await getJson('https://www.ebi.ac.uk/europepmc/webservices/rest/search', { query, format: 'json' })
    const results = json?.resultList?.result ?? []
    return results[0] || null
  }

  const pubmedFindPmidByDoi = async (doi) => {
    const json = await getJson('https://eutils.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi', {
      db: 'pubmed',
      retmode: 'json',
      term: `${doi}[DOI]`,
    })
    const ids = json?.esearchresult?.idlist ?? []
    return ids[0] || null
  }

  const pubmedSummary = async (pmid) => {
    const json = await getJson('https://eutils.ncbi.nlm.nih.gov/entrez/eutils/esummary.fcgi', {
      db: 'pubmed',
      retmode: 'json',
      id: pmid,
    })
    const s = json?.result?.[pmid]
    if (!s) return null

    return {
      pmid,
      title: s.title ?? null,
      journalTitle: s.fulljournalname ?? null,
      pubType: Array.isArray(s.pubtype) ? (s.pubtype[0] ?? null) : (s.pubtype ?? null),
      firstPublicationDate: s.pubdate ?? null,
      doi: s.elocationid ?? null, // sometimes contains DOI; not always
    }
  }

  const crossref = async (doi) => {
    const res = await useFetch(`https://api.crossref.org/works/${encodeURIComponent(doi)}`,
      { timeout: TIMEOUT, immediate: true, headers: { Accept: 'application/json' } })
      .get()
      .json()
    if (res.error.value) throw res.error.value
    const root = res.data.value
    const m = root?.message ?? {}

    const issued = m?.issued?.['date-parts']?.[0] ?? null
    const date = issued
      ? `${String(issued[0] ?? 0).padStart(4, '0')}-${String(issued[1] ?? 1).padStart(2, '0')}-${String(issued[2] ?? 1).padStart(2, '0')}`
      : null

    return {
      title: Array.isArray(m.title) ? (m.title[0] ?? null) : (m.title ?? null),
      journalTitle: Array.isArray(m['container-title']) ? (m['container-title'][0] ?? null) : (m['container-title'] ?? null),
      doi: m.DOI ?? doi,
      firstPublicationDate: date,
      pubType: m.type ?? null,
      fullTextUrlList: {
        fullTextUrl: (m.link ?? []).map((l) => ({ url: l.URL })).filter((x) => x.url),
      },
      url: `https://doi.org/${doi}`,
    }
  }

  const fetchByDoi = async (doi) => {
    // 1) Europe PMC: EXT_ID
    let hit = await epmcBy(`EXT_ID:${doi}`)
    if (hit) return hit

    // 2) Europe PMC: DOI:
    hit = await epmcBy(`DOI:${doi}`)
    if (hit) return hit

    // 3) PubMed: find PMID by DOI, then ESummary
    const pmid = await pubmedFindPmidByDoi(doi)
    if (pmid) {
      const sum = await pubmedSummary(pmid)
      if (sum) return sum
    }

    // 4) Crossref fallback
    const cr = await crossref(doi)
    if (cr) return cr

    return {}
  }

  const fetchFromUrl = async (url) => {
    const doiMatch = url.match(/10\.\d{4,9}\/\S+/)
    if (doiMatch) return await fetchByDoi(doiMatch[0])

    const pmidMatch = url.match(/\/pubmed\/(\d+)/)
    if (pmidMatch) return (await pubmedSummary(pmidMatch[1])) ?? {}

    const pmcidMatch = url.match(/\/(PMC\d+)/i)
    if (pmcidMatch) return (await epmcBy(`PMCID:${pmcidMatch[1]}`)) ?? {}

    return (await epmcBy(`EXT_ID:${url}`)) ?? {}
  }

  const fetch = async (source, id) => {
    switch (source) {
      case 'pmid':
        return (await epmcBy(`EXT_ID:${id}`)) ?? (await pubmedSummary(id)) ?? {}
      case 'pmcid':
        return (await epmcBy(`EXT_ID:${id}`)) ?? (await epmcBy(`PMCID:${id}`)) ?? {}
      case 'doi':
        return await fetchByDoi(id)
      case 'url':
        return await fetchFromUrl(id)
      default:
        return {}
    }
  }

  const extractDate = (meta) => meta?.firstPublicationDate ?? meta?.pubdate ?? null
  const extractType = (meta) => meta?.pubType ?? null
  const extractUrl = (m) => {
    if (m?.pmid) return `https://pubmed.ncbi.nlm.nih.gov/${m.pmid}/`
    if (m?.pmcid) return `https://www.ncbi.nlm.nih.gov/pmc/articles/${String(m.pmcid).toUpperCase()}/`
    if (m?.doi) return `https://doi.org/${String(m.doi).trimStart()}`
    const list = m?.fullTextUrlList?.fullTextUrl ?? []
    for (const u of list) {
      if (u?.url) return u.url
    }
    if (m?.id && m?.source) return `https://europepmc.org/article/${m.source}/${m.id}`
    return null
  }

  return {
    fetch,
    fetchByDoi,
    fetchFromUrl,
    epmcBy,
    pubmedFindPmidByDoi,
    pubmedSummary,
    crossref,
    extractDate,
    extractType,
    extractUrl,
  }
}

export default useRemotePublicationClient
