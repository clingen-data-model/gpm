export function scopeOfWorkChangeLabel(change) {
  const before = change.before_value?.value ?? change.before_value
  const after = change.after_value?.value ?? change.after_value
  const isRename = change.rule_key === 'panel_name.rename' || change.label === 'Rename Expert Panel'
  const detail = isRename && typeof before === 'string' && typeof after === 'string'
    ? `${before} to ${after}`
    : change.entity_label
  return detail ? `${change.label} - ${detail}` : change.label
}
