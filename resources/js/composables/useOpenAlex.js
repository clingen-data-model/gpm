import { ref } from "vue";

const OPENALEX_API_KEY = "";

function stripDoiUrl(value) {
  return value.replace(/^https?:\/\/doi\.org\//i, "");
}

function tailId(value) {
  return String(value || "").replace(/^.*\//, "");
}

function detectIdentifier(raw) {
  const value = (raw || "").trim();

  if (!value) return null;

  // DOI URL
  if (/^https?:\/\/doi\.org\/10\.\S+$/i.test(value)) {
    const doi = stripDoiUrl(value);
    return { type: "doi", normalized: doi, path: `doi:${doi}` };
  }

  // DOI: 10.xxxx
  if (/^doi:\s*10\.\S+$/i.test(value)) {
    const doi = value.replace(/^doi:\s*/i, "").trim();
    return { type: "doi", normalized: doi, path: `doi:${doi}` };
  }

  // raw DOI
  if (/^10\.\S+$/i.test(value)) {
    return { type: "doi", normalized: value, path: `doi:${value}` };
  }

  // PubMed URL
  if (/^https?:\/\/pubmed\.ncbi\.nlm\.nih\.gov\/\d+\/?$/i.test(value)) {
    const pmid = value.match(/\/(\d+)\/?$/)?.[1];
    return pmid ? { type: "pmid", normalized: pmid, path: `pmid:${pmid}` } : null;
  }

  // PMID: 12345678 or PMID:12345678
  if (/^pmid:\s*\d+$/i.test(value)) {
    const pmid = value.replace(/^pmid:\s*/i, "").trim();
    return { type: "pmid", normalized: pmid, path: `pmid:${pmid}` };
  }

  // raw PMID
  if (/^\d+$/.test(value)) {
    return { type: "pmid", normalized: value, path: `pmid:${value}` };
  }

  // PMC URL
  if (/^https?:\/\/pmc\.ncbi\.nlm\.nih\.gov\/articles\/PMC\d+\/?$/i.test(value)) {
    const pmcid = value.match(/\/(PMC\d+)\/?$/i)?.[1]?.toUpperCase();
    return pmcid ? { type: "pmcid", normalized: pmcid, path: `pmcid:${pmcid}` } : null;
  }

  // PMCID: PMC1234567 or PMCID:PMC1234567
  if (/^pmcid:\s*PMC\d+$/i.test(value)) {
    const pmcid = value.replace(/^pmcid:\s*/i, "").trim().toUpperCase();
    return { type: "pmcid", normalized: pmcid, path: `pmcid:${pmcid}` };
  }

  // raw PMCID
  if (/^PMC\d+$/i.test(value)) {
    return { type: "pmcid", normalized: value.toUpperCase(), path: `pmcid:${value.toUpperCase()}` };
  }

  return null;
}

// This function is used to build the publication metadata from the OpenAlex work object since 
// the structure of the work object can be complex and we want to extract only the relevant information in a consistent format for our application. 
// By centralizing this logic in a single function, we can ensure that any changes to how we extract 
// metadata from the work object only need to be made in one place, making our code more maintainable and easier to understand. 

function buildPublicationMeta(work) {
  return {
    title: work?.display_name ?? '',
    type: work?.type ?? null,
    published_at: work?.publication_date ?? null,
    journal: work?.primary_location?.source?.display_name ?? '',
    doi: { 
      id: work?.ids?.doi ? stripDoiUrl(work.ids.doi) : null,
      link: work?.ids?.doi ?? null,
    },
    pmid: {
      id: work?.ids?.pmid ? tailId(work.ids.pmid) : null,
      link: work?.ids?.pmid ?? null,
    },
    pmcid: {
      id: work?.ids?.pmcid ? tailId(work.ids.pmcid).toUpperCase() : null,
      link: work?.ids?.pmcid ?? null,
    },
    authors: Array.isArray(work?.authorships)
      ? work.authorships.map(a => a?.author?.display_name).filter(Boolean)
      : [],
  }
}

function normalizeOpenAlexWork(work, lookup) {
  const meta = buildPublicationMeta(work)
  const source = lookup.type

  const pubDefault =
    source === 'doi' ? meta.doi :
    source === 'pmid' ? meta.pmid :
    source === 'pmcid' ? meta.pmcid :
    lookup.normalized

  return {
    source,
    identifier: pubDefault?.id ?? lookup.normalized,
    link: pubDefault.link,
    pub_type: meta.type,
    published_at: meta.published_at,
    meta,
  }
}

export default function useOpenAlex() {
  const loading = ref(false);

  async function lookupIdentifier(raw) {
    const parsed = detectIdentifier(raw);
    if (!parsed) {
      throw new Error("Please enter a DOI, PMID, or PMCID.");
    }

    if (!OPENALEX_API_KEY) {
      throw new Error("OpenAlex API key is missing.");
    }

    loading.value = true;
    try {
      const url = new URL(`https://api.openalex.org/works/${parsed.path}`);
      url.searchParams.set("api_key", OPENALEX_API_KEY);

      const res = await fetch(url.toString(), {
        method: "GET",
        headers: { Accept: "application/json" },
      });

      if (!res.ok) {
        if (res.status === 404) {
          throw new Error("No publication found.");
        }
        throw new Error("API request lookup failed.");
      }

      const work = await res.json();
      return normalizeOpenAlexWork(work, parsed);
    } finally {
      loading.value = false;
    }
  }

  return {
    loading,
    lookupIdentifier,
    detectIdentifier,
    normalizeOpenAlexWork,
  };
}