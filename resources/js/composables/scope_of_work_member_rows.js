import { computed, reactive, unref } from 'vue'

// Display only: the server owns change detection; live objects and roles stay untouched.
export function useScopeOfWorkMemberRows(getMembers, getComparison, getGroupKey = () => '') {
  const expanded = reactive(new Set())
  const rows = computed(() => {
    const comparison = unref(getComparison())
    const captured = comparison?.status === 'unavailable' || comparison?.unavailable_sections?.includes('members')
      ? [] : comparison?.rows?.members ?? []
    const byPerson = new Map(captured.map(row => [String(row.key), row]))
    const seen = new Set()
    const wrap = (member, personKey, metadata, snapshotOnly) => {
      const key = `${getGroupKey()}:person:${personKey}`
      const person = snapshotOnly ? {
        first_name: member.first_name ?? member.label,
        last_name: member.last_name,
        email: member.email,
      } : member.person
      return {
        key, uuid: key, member, comparison: metadata, snapshotOnly,
        // Table projections only. Never put historical roles into member.roles.
        id: member.id, person, roles: member.roles ?? [],
        end_date: member.end_date,
        isRetired: snapshotOnly ? Object.hasOwn(member, 'end_date') && member.end_date !== null : member.isRetired,
        coi_last_completed: snapshotOnly ? undefined : member.coi_last_completed,
        get showDetails() { return expanded.has(key) },
        set showDetails(value) { value ? expanded.add(key) : expanded.delete(key) },
      }
    }
    const result = (getMembers() ?? []).map(member => {
      const key = String(member.person_id)
      seen.add(key)
      return wrap(member, key, byPerson.get(key) ?? null, false)
    })
    for (const comparisonRow of captured) {
      const key = String(comparisonRow.key)
      const snapshot = comparisonRow.after ?? comparisonRow.before
      if (seen.has(key) || !snapshot) continue
      seen.add(key)
      result.push(wrap(JSON.parse(JSON.stringify(snapshot)), key, comparisonRow, true))
    }
    return result
  })
  const canUseLiveMember = row => !!row && !row.snapshotOnly && row.comparison?.operation !== 'removed'
    && (getMembers() ?? []).includes(row.member)
  return { rows, canUseLiveMember }
}

export function memberRetirementTransition(comparison) {
  const change = comparison?.field_changes?.find(field => field.field === 'end_date')
  if (!change || comparison?.unavailable_fields?.includes('end_date')) return ''
  if ((change.before === null) === (change.after === null)) return ''
  return change.before === null ? 'Active → Retired' : 'Retired → Active'
}
