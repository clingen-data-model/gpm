import { ref, watch } from 'vue'
import { api } from '@/http'

export function useScopeOfWorkComparison(getIdentifiers) {
  const comparison = ref(null)
  const loading = ref(false)
  const error = ref('')
  const retry = ref(0)
  watch(() => {
    const [groupUuid, id, source = 'submitted', refreshKey = null] = getIdentifiers()
    return [groupUuid, id, source, refreshKey, retry.value]
  }, async ([groupUuid, id, source], _, onCleanup) => {
    let current = true
    onCleanup(() => { current = false })
    comparison.value = null
    error.value = ''
    loading.value = false
    if (!groupUuid || !id) return
    loading.value = true
    try {
      const base = `/api/groups/${encodeURIComponent(groupUuid)}`
      const url = source === 'live'
        ? `${base}/scope-of-work/revisions/${encodeURIComponent(id)}/comparison`
        : `${base}/application/submission/${encodeURIComponent(id)}/scope-of-work/comparison`
      const response = await api.get(url)
      if (current) comparison.value = response.data
    } catch {
      if (current) error.value = source === 'live'
        ? 'The draft comparison could not be loaded.'
        : 'The submitted comparison could not be loaded.'
    } finally {
      if (current) loading.value = false
    }
  }, { immediate: true })
  
  return { comparison, loading, error, retry }

}
