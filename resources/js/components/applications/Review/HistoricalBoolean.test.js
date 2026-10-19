import { mount } from '@vue/test-utils'
import { describe, it, expect } from 'vitest'
import ReviewRoundDetail from './ReviewRoundDetail.vue'
import HistoricalValue from './HistoricalValue.vue'
const render = (value, available = true, field = 'reanalysis_conflicting') => mount(ReviewRoundDetail, { props: { detail: {
  availability: 'available', mode: 'submitted_state', submitted_state: [{ key: `relations.expertPanel.attributes.${field}`, label: 'Application field', available, value }],
} } })
describe('historical checkbox presentation', () => {
  it.each([[true, 'Yes'], [1, 'Yes'], ['1', 'Yes'], [false, 'No'], [0, 'No'], ['0', 'No']])('formats %s as %s inline', (value, text) => {
    const wrapper = render(value)
    expect(wrapper.text().replace(/\s+/g, ' ')).toBe(`Application field: ${text}`)
    expect(wrapper.get('section').classes()).toContain('flex')
  })
  it.each(['utilize_gt', 'biocurator_training', 'reanalysis_review_lp', 'reanalysis_review_lb'])('formats known GCEP/VCEP/SC-VCEP checkbox %s', field => {
    expect(render('1', true, field).text()).toContain('Yes')
  })
  it('preserves unavailable and unrecorded values', () => {
    expect(render(undefined, false).text()).toContain('Historical section unavailable')
    expect(render(null).text()).toContain('Not recorded')
    expect(render(undefined).text()).not.toContain(': No')
  })
  it.each([0, 1, '0', '1'])('keeps ordinary numeric value %s numeric', value => {
    expect(render(value, true, 'meeting_count').text().replace(/\s+/g, ' ')).toBe(`Application field: ${value}`)
    expect(mount(HistoricalValue, { props: { value } }).text()).toBe(String(value))
  })
  it('keeps long text in its block layout', () => {
    expect(render('Long application text', true, 'scope_description').get('section').classes()).not.toContain('flex')
  })
  it('formats checkbox changes in later historical rounds', () => {
    const wrapper = mount(ReviewRoundDetail, { props: { detail: { availability: 'available', mode: 'previous_review_round',
      comparison: { rows: {}, changes: [{ key: 'relations.expertPanel.attributes.reanalysis_review_lp', label: 'Re-review', before: '0', after: '1' }] },
    } } })
    expect(wrapper.get('del').text()).toContain('No')
    expect(wrapper.get('ins').text()).toContain('Yes')
  })
  it('formats nested attestation flags without converting IDs', () => {
    const wrapper = mount(HistoricalValue, { props: { value: { attestation_completed: 1, id: 1 } } })
    expect(wrapper.findAll('dd').map(dd => dd.text())).toEqual(['Yes', '1'])
  })
})
