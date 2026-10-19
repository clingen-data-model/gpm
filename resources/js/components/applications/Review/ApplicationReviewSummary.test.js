import { mount } from '@vue/test-utils'
import { describe, it, expect } from 'vitest'
import ApplicationReviewSummary from './ApplicationReviewSummary.vue'

const counts = n => ({ required_revision: { outstanding: n, resolved: n + 1 },
  internal_comment: { outstanding: n + 2, resolved: n + 3 }, suggestion: { outstanding: n + 4, resolved: n + 5 } })
describe('Application Review Summary', () => {
  it.each(['VCEP', 'SC-VCEP'])('renders independent tables for %s', () => {
    const wrapper = mount(ApplicationReviewSummary, { props: { summary: { step1Summary: counts(1), step4Summary: counts(7) } } })
    const tables = wrapper.findAll('table')
    expect(tables).toHaveLength(2)
    expect(tables[0].findAll('td').map(cell => cell.text())).toEqual(['1', '3', '5', '2', '4', '6'])
    expect(tables[1].findAll('td').map(cell => cell.text())).toEqual(['7', '9', '11', '8', '10', '12'])
    expect(wrapper.findAll('h3').map(h => h.text())).toEqual(['Step 1 Review', 'Step 4 Review'])
    expect(wrapper.find('.grid').classes()).toContain('lg:grid-cols-2')
    expect(wrapper.find('.grid').classes()).toContain('grid-cols-1')
  })
  it('renders only Step 1 for GCEP', () => {
    const wrapper = mount(ApplicationReviewSummary, { props: { summary: { step1Summary: counts(0), step4Summary: null } } })
    expect(wrapper.findAll('table')).toHaveLength(1)
    expect(wrapper.text()).not.toContain('Step 4')
  })
  it('preserves loading and error states', () => {
    expect(mount(ApplicationReviewSummary, { props: { loading: true } }).find('table').exists()).toBe(false)
    expect(mount(ApplicationReviewSummary, { props: { error: 'Unavailable' } }).get('[role="alert"]').text()).toBe('Unavailable')
  })
})
