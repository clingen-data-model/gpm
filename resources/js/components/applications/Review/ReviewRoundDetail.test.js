import { mount } from '@vue/test-utils'
import { describe, it, expect } from 'vitest'
import ReviewRoundDetail from './ReviewRoundDetail.vue'

const row = (key, operation = 'unchanged', extra = {}) => ({ key, operation,
  before: operation === 'added' ? null : { label: key }, after: operation === 'removed' ? null : { label: key },
  field_changes: [], roles: [], ...extra })
const render = (rows = {}, changes = []) => mount(ReviewRoundDetail, { props: { detail: {
  mode: 'previous_review_round', availability: 'available', comparison: { rows, changes, unavailable_sections: [] },
} } })

describe('historical comparison differences only', () => {
  it.each(['added', 'removed', 'changed'])('shows a %s gene while omitting unchanged genes', operation => {
    const wrapper = render({ genes: [row('UnchangedGene'), row('ChangedGene', operation)] })
    expect(wrapper.text()).toContain('ChangedGene')
    expect(wrapper.text()).not.toContain('UnchangedGene')
    expect(wrapper.findAll('h5').map(h => h.text())).toEqual(['Genes'])
    wrapper.unmount()
  })

  it('omits empty gene/member headings and large unchanged lists', () => {
    const wrapper = render({ genes: Array.from({ length: 1100 }, (_, id) => row(`Gene${id}`)), members: [row('Unchanged person')] })
    expect(wrapper.findAll('h5')).toHaveLength(0)
    expect(wrapper.text()).toContain('No tracked changes detected for this Review Round.')
    expect(wrapper.text()).not.toContain('Unchanged person')
    expect(wrapper.text()).not.toContain('Gene1000')
    wrapper.unmount()
  })

  it('shows role-only differences without unchanged roles or whole-member badges', () => {
    const member = row('Jane Smith', 'changed', { roles: [row('Chair', 'removed'), row('Reviewer', 'added'), row('UnchangedRole')] })
    const rows = { members: [row('Unchanged person'), member] }
    const before = JSON.stringify(rows)
    const wrapper = render(rows)
    expect(wrapper.text()).toContain('Jane Smith')
    expect(wrapper.text()).toContain('Chair — Removed')
    expect(wrapper.text()).toContain('Reviewer — Added')
    expect(wrapper.text()).not.toContain('UnchangedRole')
    expect(wrapper.text()).not.toContain('Unchanged person')
    expect(wrapper.text()).not.toMatch(/Jane Smith — (Added|Removed)/)
    expect(JSON.stringify(rows)).toBe(before)
    wrapper.unmount()
  })

  it.each(['added', 'removed'])('shows a wholly %s member once with plain role context', operation => {
    const wrapper = render({ members: [row('Jane Smith', operation, { roles: [row('Chair', operation), row('Member', operation)] })] })
    expect(wrapper.text()).toContain('Jane Smith')
    expect(wrapper.text()).toContain('Chair')
    expect(wrapper.findAll(operation === 'added' ? 'ins' : 'del')).toHaveLength(1)
    wrapper.unmount()
  })

  it.each([[null, '2026-01-01', 'Active → Retired'], ['2026-01-01', null, 'Retired → Active']])('shows retirement transitions (%s → %s)', (before, after, text) => {
    const wrapper = render({ members: [row('Jane Smith', 'changed', { field_changes: [{ field: 'end_date', before, after }] })] })
    expect(wrapper.text()).toContain(`Retirement: ${text}`)
    expect(wrapper.text()).not.toContain('No tracked changes')
    wrapper.unmount()
  })

  it('shows actual member name changes and omits unchanged text fields', () => {
    const wrapper = render({ members: [row('name', 'changed', { before: { label: 'Old name' }, after: { label: 'New name' } })] },
      [{ section: 'scope_description', before: 'Same scope', after: 'Same scope' },
        { section: 'membership_description', before: 'Before', after: 'After' }])
    expect(wrapper.findAll('h5').map(h => h.text())).toEqual(['Membership description', 'Members'])
    expect(wrapper.text()).not.toContain('Same scope')
    expect(wrapper.find('del').exists()).toBe(true)
    wrapper.unmount()
  })

  it('retains the full captured initial Round 1 submitted-state view', () => {
    const wrapper = mount(ReviewRoundDetail, { props: { detail: { mode: 'submitted_state', availability: 'available',
      submitted_state: [{ label: 'Genes', available: true, value: ['CapturedGene'] },
        { label: 'Members', available: true, value: [{ name: 'Captured member', roles: ['Chair', 'Member'] }] }] } } })
    expect(wrapper.text()).toContain('CapturedGene')
    expect(wrapper.text()).toContain('Captured member')
    expect(wrapper.text()).toContain('Chair')
    expect(wrapper.text()).not.toContain('No tracked changes')
    wrapper.unmount()
  })
})
