import { geneFieldLabel, geneFieldValue } from '@/components/applications/Review/historical_gene_fields'

const scalar = value => value?.value ?? value
const text = value => value == null || value === '' ? 'Not recorded' : String(value)
const compact = value => {
  const normalized = text(value).replace(/\s+/g, ' ')
  return normalized.length > 160 ? `${normalized.slice(0, 160)}…` : normalized
}
export const revisionFieldLabel = field => geneFieldLabel(field)
  ?? ({ disease_entity: 'Disease entity', date_approved: 'Approval date', gt_curation_uuid: 'Curation ID' })[field]
  ?? field?.replaceAll('_', ' ')

function description(change, panelType) {
  const label = change.label?.replace(/Mondo Id/gi, 'MONDO ID')
  const base = change.entity_label ? `${label} — ${change.entity_label}` : label
  const before = scalar(change.before_value)
  const after = scalar(change.after_value)
  if (change.rule_key === 'panel_name.rename' && typeof before === 'string' && typeof after === 'string') {
    return `Rename Expert Panel — "${before}" → "${after}"`
  }
  const field = change.field_name
  if (['gene.update', 'gene.update_tier'].includes(change.rule_key)
    && Object.hasOwn(before ?? {}, field) && Object.hasOwn(after ?? {}, field)) {
    const values = [before[field], after[field]].map(value => geneFieldValue(field, value, panelType))
    if (values.every(value => value == null || typeof value !== 'object')) {
      const title = field === 'tier' ? 'Update gene tier' : `Update gene: ${revisionFieldLabel(field)}`
      return `${title}${change.entity_label ? ` — ${change.entity_label}` : ''}: ${values.map(compact).join(' → ')}`
    }
  }
  return base
}

// Group display rows only; retain original changes for permissions and single-field discard.
export function scopeOfWorkRevisionRows(changes = [], panelType) {
  const rows = []
  const diseases = new Map()
  for (const change of changes) {
    const isDisease = change.rule_key === 'gene.update' && ['mondo_id', 'disease_name'].includes(change.field_name)
    const identity = change.entity_uuid ?? change.after_value?.id ?? change.before_value?.id
    let row = isDisease && identity != null ? diseases.get(String(identity)) : null
    if (!row) {
      row = { key: change.id, changes: [], description: description(change, panelType) }
      rows.push(row)
      if (isDisease && identity != null) diseases.set(String(identity), row)
    }
    row.changes.push(change)
    row.requires_approval = row.changes.some(item => item.requires_approval === 'yes') ? 'yes'
      : row.changes.some(item => item.requires_approval === 'conditional') ? 'conditional' : 'no'
    if (isDisease) {
      const pair = side => {
        const captured = Object.assign({}, ...row.changes.map(item => item.display_gene?.[side]))
        for (const item of row.changes) {
          const value = item[`${side}_value`]
          if (Object.hasOwn(value ?? {}, item.field_name)) captured[item.field_name] = value[item.field_name]
        }
        return captured
      }
      const before = pair('before'), after = pair('after')
      const disease = gene => [gene.mondo_id, gene.disease_name].filter(value => value != null && value !== '').join(' ') || 'Not recorded'
      const symbol = after.gene_symbol ?? before.gene_symbol ?? change.entity_label?.split(' - ')[0]
      row.description = `Update gene disease${symbol ? ` — ${symbol}` : ''}: ${disease(before)} → ${disease(after)}`
    }
  }
  return rows
}
