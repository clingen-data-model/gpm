const canonical = value => {
  if (Array.isArray(value)) return value.map(canonical)
  if (value && typeof value === 'object') return Object.fromEntries(Object.keys(value).sort().map(key => [key, canonical(value[key])]))
  return value
}
const signature = value => JSON.stringify(canonical(value))
const identity = item => {
  if (!item || typeof item !== 'object') return null
  if (item.id != null) return `id:${item.id}`
  if (item.name) return `name:${item.name}`
  return null
}

const legacyContext = item => {
  if (!item || item.id != null || !item.gene) return null
  const disease = item.mondo_id ? ['mondo', item.mondo_id] : item.disease ? ['disease', item.disease] : null
  return disease ? signature([item.gene, ...disease]) : null
}

// Legacy evidence: after exact matches, require one unmatched ID-less entry per
// side with the same gene + MONDO (or disease when MONDO is absent). Never use
// variant, assertion, summary, URL or array position. Never mix ID and ID-less
// records. Duplicate identical originals remain ambiguous even after subtraction.
// Match exact entries first (including duplicate occurrences). Never pair by position
// or guess which of several entries with the same captured identity was edited.
export const sustainedCurationChanges = (before, after) => {
  if (!Array.isArray(before) || !Array.isArray(after)) return null
  const remaining = after.map((value, index) => ({ value, index }))
  const old = []
  for (const value of before) {
    const index = remaining.findIndex(item => signature(item.value) === signature(value))
    if (index >= 0) remaining.splice(index, 1)
    else old.push(value)
  }
  const unmatchedAfter = remaining.map(item => item.value)
  const rows = []
  for (const value of old) {
    const key = identity(value)
    const uniqueBefore = key && before.filter(item => identity(item) === key).length === 1
    const uniqueAfter = key && after.filter(item => identity(item) === key).length === 1
    let index = uniqueBefore && uniqueAfter ? remaining.findIndex(item => identity(item.value) === key) : -1
    const context = legacyContext(value)
    if (index < 0 && context
      && old.filter(item => legacyContext(item) === context).length === 1
      && unmatchedAfter.filter(item => legacyContext(item) === context).length === 1
      && before.filter(item => signature(item) === signature(value)).length === 1) {
      index = remaining.findIndex(item => legacyContext(item.value) === context)
    }
    if (index >= 0) rows.push({ operation: 'Changed', before: value, after: remaining.splice(index, 1)[0].value })
    else rows.push({ operation: 'Removed', before: value })
  }
  for (const item of remaining) rows.push({ operation: 'Added', after: item.value })
  return rows
}
