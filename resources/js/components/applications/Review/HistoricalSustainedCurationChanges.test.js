import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import ReviewRoundDetail from './ReviewRoundDetail.vue'
import { sustainedCurationChanges } from './sustained_curation_changes'
const evidence = (gene, summary = 'Original') => ({ gene, variant: 'v1', mondo_id: 'MONDO:1', summary })
const render = (label, before, after) => mount(ReviewRoundDetail, { props: { detail: {
  step: 4, availability: 'available', mode: 'previous_review_round', comparison: { rows: {}, changes: [{ label, before, after }] },
} } })
describe('changed-only Step 4 history', () => {
  it('omits unchanged evidence and identifies added, removed and edited cards', () => {
    const before = [evidence('UNCHANGED'), evidence('EDITED'), evidence('REMOVED')]
    const after = [evidence('NEW'), evidence('UNCHANGED'), evidence('EDITED', 'Updated')]
    const wrapper = render('Evidence Summaries', before, after)
    expect(wrapper.text()).not.toContain('UNCHANGED')
    for (const text of ['Changed', 'Original', 'Updated', 'Added', 'NEW', 'Removed', 'REMOVED']) expect(wrapper.text()).toContain(text)
    expect(wrapper.find('table').exists()).toBe(false)
    expect(before[0].gene).toBe('UNCHANGED')
    expect(after).toHaveLength(3)
  })
  it.each(['Biocurator Trainers', 'Trained Biocurators'])('shows only added and removed %s names', label => {
    const wrapper = render(label, [{ name: 'Unchanged' }, { name: 'Retired' }], [{ name: 'New' }, { name: 'Unchanged' }])
    expect(wrapper.text()).not.toContain('Unchanged')
    for (const text of ['Added', 'New', 'Removed', 'Retired']) expect(wrapper.text()).toContain(text)
  })
  it('shows only members whose captured attestations changed', () => {
    const same = { name: 'Unchanged member', attestation_completed: true }
    const wrapper = render('Core Approval Members', [same, { name: 'Edited member', attestation_completed: false }],
      [{ name: 'Edited member', attestation_completed: true, attestation_completion_date: '2026-10-15' }, same])
    expect(wrapper.text()).not.toContain('Unchanged member')
    for (const text of ['Changed', 'Edited member', 'Yes', 'No', 'Oct 15, 2026']) expect(wrapper.text()).toContain(text)
  })
  it('ignores list reordering and object property ordering', () => {
    const wrapper = render('Evidence Summaries', [evidence('A'), evidence('B')], [{ summary: 'Original', mondo_id: 'MONDO:1', variant: 'v1', gene: 'B' }, evidence('A')])
    expect(wrapper.text()).toContain('No tracked changes detected')
    expect(wrapper.find('h5').exists()).toBe(false)
  })
  it('handles duplicate occurrences without guessing which item changed', () => {
    const same = evidence('DUPLICATE')
    const rows = sustainedCurationChanges([same, same], [same, evidence('DUPLICATE', 'Edited')])
    expect(rows.map(row => row.operation)).toEqual(['Removed', 'Added'])
    expect(sustainedCurationChanges([same, same], [same])).toEqual([{ operation: 'Removed', before: same }])
  })
  it('does not pair unrelated entries by array position', () => {
    expect(sustainedCurationChanges([evidence('A')], [evidence('B')]).map(row => row.operation)).toEqual(['Removed', 'Added'])
  })
  it('preserves unknown historical values rather than inventing additions', () => {
    const wrapper = render('Evidence Summaries', null, [evidence('A')])
    expect(wrapper.text()).toContain('Not recorded')
    expect(wrapper.text()).not.toContain('Added')
    expect(wrapper.text()).toContain('Previous')
  })
  it('retains the full existing Round 1 renderer', () => {
    const wrapper = mount(ReviewRoundDetail, { props: { detail: { availability: 'available', step: 4, mode: 'submitted_state',
      submitted_state: [{ label: 'Evidence Summaries', available: true, value: [evidence('A'), evidence('B')] }],
    } } })
    expect(wrapper.text()).toContain('A')
    expect(wrapper.text()).toContain('B')
    expect(wrapper.text()).not.toMatch(/Added|Removed|Changed/)
    expect(wrapper.find('table').exists()).toBe(false)
  })
})
