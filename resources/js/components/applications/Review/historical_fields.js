// Only application checkbox fields, never arbitrary numeric snapshot values.
const booleanFields = new Set([
  'reanalysis_conflicting', 'reanalysis_review_lp', 'reanalysis_review_lb',
  'utilize_gt', 'utilize_gci', 'curations_publicly_available', 'pub_policy_reviewed',
  'draft_manuscripts', 'recuration_process_review', 'biocurator_training', 'biocurator_mailing_list',
  'attestation_completed', 'training_level_1', 'training_level_2',
])
export const isHistoricalBooleanField = key => booleanFields.has(String(key ?? '').split('.').at(-1))
export const historicalBoolean = value => {
  if ([true, 1, '1'].includes(value)) return 'Yes'
  if ([false, 0, '0'].includes(value)) return 'No'
  return value
}
export const historicalFieldValue = (key, value) => isHistoricalBooleanField(key) ? historicalBoolean(value) : value
export const isInlineHistoricalField = section => {
  if (section.value !== null && typeof section.value === 'object') return false
  if (/description|notes|rationale|reanalysis_other|protocol_other/i.test(section.key ?? section.label)) return false
  return String(section.value ?? '').length <= 120 && !String(section.value ?? '').includes('\n')
}
