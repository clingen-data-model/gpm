import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import { sustainedCurationChanges } from './sustained_curation_changes'
import HistoricalSustainedCurationChanges from './HistoricalSustainedCurationChanges.vue'
import HistoricalSustainedCuration from './HistoricalSustainedCuration.vue'
import InlineTextDiff from '@/components/groups/InlineTextDiff.vue'
const original = { id: 987654, gene: 'NTRKR3', mondo_id: 'MONDO:0000163', disease: 'Old disease',
  variant: 'kjhjkjhkhjk', assertion_id: '79898', summary: 'Shared old text', 'CIViC URL': null }
const legacy = ({ id, ...rest }) => rest
const render = (before, after) => mount(HistoricalSustainedCurationChanges, { props: { change: { label: 'Evidence Summaries', before, after } } })
describe('stable EvidenceSummary identity', () => {
  it.each([
    { summary: 'Shared new text' }, { assertion_id: '123456' }, { variant: 'abcabcabcabc' },
    { mondo_id: 'MONDO:2', disease: 'New disease' }, { 'CIViC URL': 'https://example.org/civic' },
    { gene: 'OTHER', mondo_id: 'MONDO:2', variant: 'new', assertion_id: 'new', summary: 'new' },
  ])('pairs same ID independently of mutable values: %j', changes => {
    const after = { ...original, ...changes }
    expect(sustainedCurationChanges([original], [after])).toEqual([{ operation: 'Changed', before: original, after }])
  })
  it('omits identical IDs/content, including reordered records', () => {
    const other = { ...original, id: 2 }
    expect(sustainedCurationChanges([original, other], [other, original])).toEqual([])
  })
  it('treats different IDs as removed/added even if context is identical', () => {
    expect(sustainedCurationChanges([original], [{ ...original, id: 2 }]).map(row => row.operation)).toEqual(['Removed', 'Added'])
    expect(sustainedCurationChanges([], [original])[0].operation).toBe('Added')
    expect(sustainedCurationChanges([original], [])[0].operation).toBe('Removed')
  })
  it('does not infer identity across mixed ID-less and ID-bearing snapshots', () => {
    expect(sustainedCurationChanges([legacy(original)], [original]).map(row => row.operation)).toEqual(['Removed', 'Added'])
    expect(sustainedCurationChanges([original], [legacy(original)]).map(row => row.operation)).toEqual(['Removed', 'Added'])
  })
  it('keeps IDs internal in changed, added, removed and Round 1 views', () => {
    for (const wrapper of [render([original], [{ ...original, summary: 'New' }]), render([], [original]), render([original], []),
      mount(HistoricalSustainedCuration, { props: { section: { label: 'Evidence Summaries', available: true, value: [original] } } })]) {
      expect(wrapper.text()).not.toContain('987654')
    }
  })
})
describe('conservative legacy context', () => {
  it('pairs one unmatched entry after exact matches, reproducing snapshot 141/142 context', () => {
    const before = legacy(original)
    const unchangedCompetitor = { ...before, variant: 'dsfdfsdf', assertion_id: '213131', summary: 'Unchanged fifth evidence' }
    const after = { ...before, variant: 'abcabcabcabc', assertion_id: '123456', summary: 'Edited text', 'CIViC URL': 'https://example.org' }
    expect(sustainedCurationChanges([before, unchangedCompetitor], [unchangedCompetitor, after])).toEqual([{ operation: 'Changed', before, after }])
  })
  it('leaves competing unmatched contextual candidates unpaired', () => {
    const before = legacy(original)
    const other = { ...before, variant: 'other' }
    const result = sustainedCurationChanges([before, other], [{ ...before, summary: 'new' }, { ...other, summary: 'newer' }])
    expect(result.map(row => row.operation)).toEqual(['Removed', 'Removed', 'Added', 'Added'])
  })
  it('does not match a changed gene/MONDO or a missing context', () => {
    const before = legacy(original)
    expect(sustainedCurationChanges([before], [{ ...before, gene: 'OTHER', mondo_id: 'MONDO:2' }]).map(row => row.operation)).toEqual(['Removed', 'Added'])
    expect(sustainedCurationChanges([{ summary: 'old' }], [{ summary: 'new' }]).map(row => row.operation)).toEqual(['Removed', 'Added'])
  })
})
it('renders one Changed card with paired disease, short field diffs, inline summary, and context once', () => {
  const after = { ...original, mondo_id: 'MONDO:2', disease: 'New disease', variant: 'abcabcabcabc', assertion_id: '123456', moi: 'AD', summary: 'Shared new text', 'CIViC URL': 'https://example.org/civic' }
  const wrapper = render([original], [after])
  expect(wrapper.findAll('[data-testid="changed-evidence"]')).toHaveLength(1)
  expect(wrapper.findComponent(HistoricalSustainedCuration).exists()).toBe(false)
  expect(wrapper.text()).not.toMatch(/Previous|Submitted/)
  expect(wrapper.text().split('NTRKR3')).toHaveLength(2)
  const disease = wrapper.get('[data-field="Disease / MONDO"]')
  expect(disease.get('del').text()).toBe('MONDO:0000163 Old disease')
  expect(disease.get('ins').text()).toBe('MONDO:2 New disease')
  for (const field of ['Variant', 'Assertion ID', 'MOI', 'CIViC']) {
    expect(wrapper.get(`[data-field="${field}"]`).find('del').exists()).toBe(true)
    expect(wrapper.get(`[data-field="${field}"]`).find('ins').exists()).toBe(true)
  }
  expect(wrapper.get('a').text()).toBe('View CIViC')
  expect(wrapper.get('a').attributes('href')).toBe('https://example.org/civic')
  expect(wrapper.findComponent(InlineTextDiff).props()).toEqual({ before: 'Shared old text', after: 'Shared new text' })
})


describe('compact updated evidence cards', () => {
  it.each([
    { variant: 'Updated variant' }, { assertion_id: 'Updated assertion' }, { summary: 'Updated summary' },
    { 'CIViC URL': 'https://example.org/updated' }, { mondo_id: 'MONDO:999', disease: 'Updated disease' },
    { gene: 'Updated gene' }, { moi: 'AD' },
  ])('renders a single Changed card for same-ID edit %j', changes => {
    const wrapper = render([original], [{ ...original, ...changes }])
    expect(wrapper.findAll('[data-testid="changed-evidence"]')).toHaveLength(1)
    expect(wrapper.findComponent(HistoricalSustainedCuration).exists()).toBe(false)
    expect(wrapper.find('table').exists()).toBe(false)
    expect(wrapper.text()).not.toMatch(/Previous|Submitted/)
    expect(wrapper.get('[data-testid="evidence-metadata"]').classes()).toContain('flex-wrap')
    expect(wrapper.get('[data-field="Disease / MONDO"]').classes()).toContain('bg-gray-100')
    expect(wrapper.text()).not.toContain('987654')
  })
  it('renders only edited cards, retaining genuine additions and removals', () => {
    const same = { ...original, id: 22, gene: 'UNCHANGED' }
    const other = { ...original, id: 23, gene: 'SECOND' }
    const deleted = { ...original, id: 24, gene: 'DELETED' }
    const added = { ...original, id: 25, gene: 'NEW' }
    const wrapper = render([original, same, other, deleted], [same, { ...other, summary: 'Changed second' }, added, { ...original, variant: 'Changed first' }])
    expect(wrapper.findAll('[data-testid="changed-evidence"]')).toHaveLength(2)
    expect(wrapper.text()).not.toContain('UNCHANGED')
    expect(wrapper.findAllComponents(HistoricalSustainedCuration)).toHaveLength(2)
    expect(wrapper.text()).toContain('DELETED')
    expect(wrapper.text()).toContain('NEW')
    expect(wrapper.findAll('[data-testid="changed-evidence"]').map(card => card.text().split('NTRKR3').length)).toContain(2)
  })
  it('keeps a transitional ID-less to ID-bearing round conservative for every entry', () => {
    const before = Array.from({ length: 5 }, (_, index) => ({ ...legacy(original), variant: `Variant ${index}` }))
    const after = before.map((item, index) => ({ ...item, id: 186 + index, summary: index === 1 || index === 3 ? 'Edited' : item.summary }))
    expect(sustainedCurationChanges(before, after).map(row => row.operation)).toEqual([
      'Removed', 'Removed', 'Removed', 'Removed', 'Removed', 'Added', 'Added', 'Added', 'Added', 'Added',
    ])
  })
})
