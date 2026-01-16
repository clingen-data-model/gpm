import { computed, toRef } from 'vue'
import { useFetch } from '@vueuse/core'

/**
 * Uses Vue patterns and a useFetch composable to map to various remote publication APIs.
 * All methods return plain JS objects (mapped to a EuropePMC-ish shape) or null/empty objects on miss.
 */
export function usePublicationLookup(query) {
  // https://www.ebi.ac.uk/europepmc/webservices/rest/search?query=EXT_ID%3A35341655&resultType=lite
  const refQuery = toRef(query)
  const url = computed(() => {
    console.log('Generating URL for query_identifier:', refQuery.value);
    return `https://www.ebi.ac.uk/europepmc/webservices/rest/search?query=${encodeURIComponent(refQuery.value)}&resultType=lite&format=json`
  });

  const { data, error, execute, pending } = useFetch(url, {
    immediate: false,
    headers: { Accept: 'application/json' },
  }).json()
  const processedData = computed(() => {
    if (error.value || !data.value || !data.value.resultList || data.value.resultList.result.length === 0) {
      console.log('No data found or error occurred:', error.value);
      return null
    }
    const result = data.value.resultList.result[0]
    console.log('Fetched publication data:', result);
    return {
      id: result.id,
      title: result.title,
      authors: result.authorString,
      journal: result.journalTitle,
      pubYear: result.pubYear,
      pubDate: result.firstPublicationDate,
      doi: result.doi,
      pmid: result.pmid,
      pmcid: result.pmcid,
    }
  })

  return {
    citation: processedData,
    rawData: data,
    error,
    execute,
    pending,
  }
}

export default usePublicationLookup
