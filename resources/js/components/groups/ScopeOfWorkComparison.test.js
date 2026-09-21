import { mount, flushPromises } from '@vue/test-utils'
import { describe, it, expect, vi } from 'vitest'
import InlineTextDiff from './InlineTextDiff.vue'
import ScopeOfWorkChangeComparison from './ScopeOfWorkChangeComparison.vue'
import ScopeOfWorkRoundComparison from './ScopeOfWorkRoundComparison.vue'
import { api } from '@/http'
vi.mock('@/http', () => ({ api: { get: vi.fn() } }))
const row = (key, operation) => ({ key, label: key, operation, before: { label: key }, after: { label: key } })
const payload = () => ({ status: 'complete', mode: 'previous_review_round', before: { submission_id: 1, snapshot_id: 1 }, after: { submission_id: 2, snapshot_id: 2 }, summary: { changed_items: 2 }, changes: [], unavailable_sections: [], rows: { genes: ['unchanged', 'added', 'removed', 'changed'].map(op => row(op, op)), members: [{ ...row('Alex', 'changed'), roles_available: true, roles: [row('Chair', 'removed'), row('Coordinator', 'added')] }, { ...row('Robin', 'unchanged'), roles_available: false }] } })
describe('Scope of Work comparison', () => {
  it('renders escaped inline additions and removals', () => {
    const wrapper = mount(InlineTextDiff, { props: { before: 'Old scope', after: '<img> scope' } })
    expect(wrapper.find('del').text()).toContain('Old')
    expect(wrapper.find('ins').text()).toContain('<img>')
    expect(wrapper.find('img').exists()).toBe(false)
  })
  it('shows all gene rows and nested roles without misclassifying the member', () => {
    const wrapper = mount(ScopeOfWorkChangeComparison, { props: { comparison: payload() } })
    expect(wrapper.text()).toContain('unchanged')
    expect(wrapper.text()).toContain('Added')
    expect(wrapper.text()).toContain('Removed')
    expect(wrapper.text()).toContain('Changed')
    expect(wrapper.text()).toContain('Coordinator')
    expect(wrapper.text()).toContain('Historical role details are unavailable')
    expect(wrapper.find('.line-through').exists()).toBe(true)
  })
  it('does not render or fetch without identifiers', () => {
    api.get.mockClear()
    const wrapper = mount(ScopeOfWorkRoundComparison)
    expect(wrapper.text()).toBe('')
    expect(api.get).not.toHaveBeenCalled()
  })
  it('ignores stale responses when the submission changes', async () => {
    let first
    api.get.mockImplementationOnce(() => new Promise(resolve => { first = resolve }))
    api.get.mockResolvedValueOnce({ data: payload() })
    const wrapper = mount(ScopeOfWorkRoundComparison, { props: { groupUuid: 'group', submissionId: 1 } })
    expect(wrapper.text()).toContain('Loading')
    await wrapper.setProps({ submissionId: 2 })
    await flushPromises()
    first({ data: { ...payload(), after: { submission_id: 999 } } })
    await flushPromises()
    expect(wrapper.text()).toContain('Submission #2')
    expect(wrapper.text()).not.toContain('#999')
  })
  it('shows partial data and retries failed requests', async () => {
    api.get.mockRejectedValueOnce(new Error('offline'))
    api.get.mockResolvedValueOnce({ data: { ...payload(), status: 'partial', unavailable_sections: ['genes'], rows: { genes: null, members: [] } } })
    const wrapper = mount(ScopeOfWorkRoundComparison, { props: { groupUuid: 'group', submissionId: 2 } })
    await flushPromises()
    expect(wrapper.text()).toContain('could not be loaded')
    await wrapper.get('button').trigger('click')
    await flushPromises()
    expect(wrapper.text()).toContain('Partial comparison')
    expect(wrapper.text()).toContain('Historical genes are unavailable')
  })
})
