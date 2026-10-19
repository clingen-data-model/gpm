export const geneFieldLabel = key => ({ gene_symbol: 'Gene', hgnc_id: 'HGNC ID', disease_name: 'Disease', mondo_id: 'MONDO ID', moi: 'MOI', tier: 'Tier', plan: 'Plan for Curation' })[key]
export const visibleGeneField = (key, type) => !!geneFieldLabel(key)
  && !(type === 'scvcep' && ['moi', 'plan'].includes(key))
  && !(type === 'gcep' && key === 'plan')
export const geneTierLabel = (value, type) => {
  if (value === null || value === undefined || value === '') return 'Not recorded'
  if (['Primary', 'Secondary', 'Current', 'Future'].includes(value)) return value
  if (![1, 2, '1', '2'].includes(value) || !['gcep', 'vcep', 'scvcep'].includes(type)) return 'Historical tier label unavailable'
  return (type === 'gcep' ? ['Primary', 'Secondary'] : ['Current', 'Future'])[Number(value) - 1]
}
export const genePlanText = plan => {
  if (plan == null || plan === '') return 'Not recorded'
  if (typeof plan === 'string') return plan
  if (plan.is_other === true) return plan.the_plan || 'Not recorded'
  if (['Moderate', 'Limited'].includes(plan.classification)) return plan.curated_plan_text || 'Not recorded'
  return 'Not applicable'
}
export const geneFieldValue = (key, value, type) => key === 'tier' ? geneTierLabel(value, type)
  : key === 'plan' ? genePlanText(value) : value
