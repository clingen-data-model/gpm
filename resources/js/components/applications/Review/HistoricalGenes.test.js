import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import HistoricalGenes from './HistoricalGenes.vue'
import ReviewRoundDetail from './ReviewRoundDetail.vue'
import { geneFieldValue, genePlanText, geneTierLabel } from './historical_gene_fields'

const gene = { gene_symbol: 'TEST', hgnc_id: 1, disease_name: 'Disease', mondo_id: 'MONDO:1', moi: 'AD', tier: 1,
  disease_entity: 'internal disease', gt_curation_uuid: 'internal uuid', plan: { is_other: true, the_plan: 'Curation rationale', checkKey: 'internal key' } }
describe('panel-specific historical genes', () => {
  it.each([
    ['scvcep', ['Gene', 'Disease / MONDO', 'Tier']],
    ['vcep', ['Gene', 'Disease / MONDO', 'MOI', 'Tier', 'Plan']],
    ['gcep', ['Gene', 'Disease / MONDO', 'MOI', 'Tier']],
  ])('%s uses its own gene columns', (groupType, columns) => {
    const wrapper = mount(HistoricalGenes, { props: { groupType, genes: [gene] } })
    expect(wrapper.findAll('th').map(th => th.text())).toEqual(columns)
    expect(wrapper.text()).not.toContain('internal')
    expect(wrapper.text().includes('Curation rationale')).toBe(groupType === 'vcep')
    expect(wrapper.text()).toContain('HGNC: 1')
  })
  it.each([['vcep', 'Current', 'Future'], ['scvcep', 'Current', 'Future'], ['gcep', 'Primary', 'Secondary']])('%s formats only gene tiers throughout history', (type, first, second) => {
    for (const [value, expected] of [[1, first], ['2', second], [null, 'Not recorded'], [undefined, 'Not recorded']]) {
      expect(geneTierLabel(value, type)).toBe(expected)
      const table = mount(HistoricalGenes, { props: { groupType: type, genes: [{ ...gene, tier: value }] } })
      expect(table.findAll('tbody td')[type === 'scvcep' ? 2 : 3].text()).toBe(expected)
    }
    const wrapper = mount(ReviewRoundDetail, { props: { detail: { availability: 'available', group_type: type, comparison: { rows: { genes: [
      { key: 'changed', operation: 'changed', after: gene, unavailable_fields: ['disease_entity', 'gt_curation_uuid', 'moi', 'plan'], field_changes: [
        { field: 'tier', before: null, after: 1 }, { field: 'disease_entity', before: 'internal before', after: 'internal after' },
        { field: 'hgnc_id', before: 1, after: 2 },
      ] },
      { key: 'added', operation: 'added', after: { ...gene, tier: 2 } },
      { key: 'removed', operation: 'removed', before: { ...gene, tier: 1 } },
    ] } } } } })
    expect(wrapper.text()).toContain('Not recorded')
    expect(wrapper.text()).toContain(first)
    expect(wrapper.text()).toContain(second)
    expect(wrapper.text()).toContain(`Tier: ${second}`)
    expect(wrapper.text()).toContain(`Tier: ${first}`)
    expect(wrapper.text()).not.toMatch(/disease_entity|gt_curation_uuid|internal/)
    if (type === 'scvcep') expect(wrapper.text()).not.toMatch(/MOI|Plan/)
    expect(geneFieldValue('hgnc_id', 1, type)).toBe(1)
    expect(geneFieldValue('hgnc_id', 2, type)).toBe(2)
    expect(wrapper.text()).toContain('HGNC ID: 1')
  })
  it('renders only applicable VCEP plan text', () => {
    expect(genePlanText({ is_other: true, the_plan: 'Other plan' })).toBe('Other plan')
    expect(genePlanText({ classification: 'Moderate', curated_plan_text: 'Moderate plan' })).toBe('Moderate plan')
    expect(genePlanText({ classification: 'Limited', curated_plan_text: 'Limited plan' })).toBe('Limited plan')
    expect(genePlanText({ classification: 'Definitive', curated_plan_text: 'Hidden stale plan' })).toBe('Not applicable')
  })
  it('does not expose internal fields in the full first round', () => {
    const wrapper = mount(ReviewRoundDetail, { props: { detail: { availability: 'available', mode: 'submitted_state', group_type: 'scvcep',
      submitted_state: [{ label: 'Genes', available: true, value: [gene] }] } } })
    expect(wrapper.text()).toContain('Current')
    expect(wrapper.text()).not.toMatch(/internal|MOI|Plan|disease_entity/)
  })
})
