import { computed, unref } from 'vue'

// Display adapter only. Operations and field differences come from the server.
export function useScopeOfWorkGeneRows(getLiveGenes, getComparison) {
  const rows = computed(() => {
    const live = getLiveGenes() ?? []
    const comparison = unref(getComparison())
    const captured = comparison?.status === 'unavailable' || comparison?.unavailable_sections?.includes('genes')
      ? [] : comparison?.rows?.genes ?? []
    const byId = new Map(captured.map(row => [String(row.key), row]))
    const seen = new Set()
    const result = live.map(gene => {
      const key = String(gene.id)
      seen.add(key)
      return { key, gene, comparison: byId.get(key) ?? null, snapshotOnly: false }
    })
    for (const row of captured) {
      const key = String(row.key)
      const snapshot = row.after ?? row.before
      if (seen.has(key) || !snapshot) continue
      seen.add(key)
      // No current GT enrichment is fetched or fabricated for historical rows.
      const gene = JSON.parse(JSON.stringify(snapshot))
      result.push({ key, gene, comparison: row, snapshotOnly: true })
    }
    return result
  })
  const entries = computed(() => new Map(rows.value.map(row => [row.key, row])))
  const rowFor = gene => entries.value.get(String(gene?.id))
  const comparisonFor = gene => rowFor(gene)?.comparison ?? null
  const isRemoved = gene => comparisonFor(gene)?.operation === 'removed'
  const isSnapshotOnly = gene => rowFor(gene)?.snapshotOnly ?? false
  const canMutate = gene => {
    const row = rowFor(gene)
    return !!row && row.gene === gene && !row.snapshotOnly && !isRemoved(gene)
  }
  const editableIds = ids => {
    const requested = new Set(ids.map(String))
    return rows.value.filter(row => requested.has(row.key) && canMutate(row.gene)).map(row => row.gene.id)
  }
  const displayGenes = computed(() => rows.value.map(row => row.gene))
  return { rows, displayGenes, comparisonFor, isRemoved, isSnapshotOnly, canMutate, editableIds }
}
