import { mount, flushPromises } from '@vue/test-utils'
import { ref } from 'vue'
import { describe, it, expect, vi, afterEach } from 'vitest'
import { readFileSync } from 'node:fs'
import { api } from '@/http'
import ReviewHistory from './ReviewHistory.vue'
import ReviewRoundDetail from './ReviewRoundDetail.vue'
import SubmissionContextSummary from './SubmissionContextSummary.vue'
import { createStore } from 'vuex'

vi.mock('@/http', () => ({ api: { get: vi.fn() } }))
vi.mock('@/auth_utils.js', () => ({ hasPermission: () => false }))
afterEach(() => vi.clearAllMocks())

const round = (id, number, mode = 'submitted_state') => ({ submission_id: id, review_round: number,
  submitted_at: '2026-09-20T12:00:00Z', status: 'Revisions Requested', closed_at: '2026-09-21T12:00:00Z',
  submitted_by: { name: 'Alex' }, submitter_notes: 'My notes', revisions_requested_notes: 'Please revise scope',
  reviewer_judgements: [{ id: 1, reviewer: { name: 'Chair' }, decision: 'request-revisions', notes: 'Chair note' }],
  snapshot: { availability: 'available' }, detail: { mode, availability: 'available' } })
const payload = () => ({ group_uuid: 'group', ambiguous_submissions: [], cycles: [
  { key: 'scope_of_work:42', title: 'Scope of Work Version 3.0', kind: 'scope_of_work',
    base_version: { label: '2.0' }, approval: { relationship: 'not_approved' },
    rounds: [{ ...round(3, 1, 'approved_baseline'), is_current_review: true }] },
  { key: 'initial_application', title: 'Initial Application', kind: 'initial_application',
    approval: { relationship: 'unrecorded' }, rounds: [round(1, 1), round(2, 2, 'previous_review_round')] },
] })
function render(data = payload()) {
  const state = { history: ref(data), loading: ref(false), error: ref('') }
  const wrapper = mount(ReviewHistory, { global: { provide: { applicationReviewHistory: state } } })
  return { wrapper, state }
}

describe('reviewer Review History', () => {
  it('starts collapsed and displays server cycles, round numbers and metadata', () => {
    const { wrapper } = render()
    expect(wrapper.get('[data-testid="review-history"]').attributes('open')).toBeUndefined()
    expect(wrapper.text()).toContain('Initial Application')
    expect(wrapper.text()).toContain('Scope of Work Version 3.0')
    expect(wrapper.text()).toContain('Based on Scope of Work Version 2.0')
    expect(wrapper.text()).toContain('Review Round 2')
    expect(wrapper.text()).toContain('Current review')
    expect(wrapper.text()).toContain('Submitter notes: My notes')
    expect(wrapper.text()).toContain('Revisions-requested notes: Please revise scope')
    expect(wrapper.text()).toContain('Chair judgement — Chair')
    expect(wrapper.text()).not.toContain('Sustained Curation')
    expect(wrapper.text()).not.toMatch(/Discard|Approve →|Edit member/)
    expect(wrapper.findAll('input,textarea,form')).toHaveLength(0)
    wrapper.unmount()
  })

  it('loads initial Round 1 submitted state from the read-only endpoint', async () => {
    api.get.mockResolvedValue({ data: { mode: 'submitted_state', availability: 'available', submitted_state: [
      { label: 'Panel name', available: true, value: '<script>Frozen</script>' },
      { label: 'Genes', available: false, value: null },
    ] } })
    const { wrapper } = render()
    await wrapper.findAll('button').find(button => button.text() === 'View submitted application').trigger('click')
    await flushPromises()
    expect(api.get).toHaveBeenCalledWith('/api/groups/group/application/review-history/1')
    expect(wrapper.text()).toContain('<script>Frozen</script>')
    expect(wrapper.find('script').exists()).toBe(false)
    expect(wrapper.text()).toContain('Historical section unavailable')
    wrapper.unmount()
  })

  it('offers changes for comparison rounds and renders scope/member/role/retirement detail', async () => {
    api.get.mockResolvedValue({ data: { mode: 'previous_review_round', availability: 'available', comparison: {
      changes: [{ section: 'scope_description', before: 'Old scope', after: 'New scope' }],
      unavailable_sections: ['genes'], rows: { members: [{ key: '7', operation: 'changed', after: { label: 'Jane' },
        field_changes: [{ field: 'end_date', before: null, after: '2026-01-01' }],
        roles: [{ key: 'chair', operation: 'removed', before: { label: 'Chair' } },
          { key: 'reviewer', operation: 'added', after: { label: 'Reviewer' } }] }] },
    } } })
    const { wrapper } = render()
    await wrapper.findAll('button').find(button => button.text() === 'View changes').trigger('click')
    await flushPromises()
    expect(wrapper.text()).toContain('Genes: Historical section unavailable')
    expect(wrapper.text()).toContain('Retirement: Active → Retired')
    expect(wrapper.text()).toContain('Chair — Removed')
    expect(wrapper.text()).toContain('Reviewer — Added')
    expect(wrapper.find('del').exists()).toBe(true)
    expect(wrapper.find('ins').exists()).toBe(true)
    wrapper.unmount()
  })

  it.each(['unavailable', 'ambiguous'])('retains metadata for %s snapshots', availability => {
    const data = payload()
    data.cycles[1].rounds[0].snapshot.availability = availability
    data.cycles[1].rounds[0].detail.availability = availability
    const { wrapper } = render(data)
    expect(wrapper.text()).toContain('Submitted application details are unavailable for this historical Review Round.')
    expect(wrapper.text()).toContain(`Historical snapshot: ${availability}`)
    expect(wrapper.findAll('button').some(button => button.text() === 'View submitted application')).toBe(false)
    wrapper.unmount()
  })

  it('never renders comparison payload when detail is unavailable', () => {
    const wrapper = mount(ReviewRoundDetail, { props: { detail: { availability: 'unavailable',
      comparison: { changes: [{ section: 'scope_description', after: 'Must not show' }] } } } })
    expect(wrapper.text()).not.toContain('Must not show')
    expect(wrapper.text()).toContain('unavailable')
    wrapper.unmount()
  })

  it('clears expanded detail on refresh and ignores responses from an older group', async () => {
    let resolve
    api.get.mockReturnValue(new Promise(done => { resolve = done }))
    const { wrapper, state } = render()
    await wrapper.findAll('button')[0].trigger('click')
    expect(wrapper.findAll('button')[0].attributes('disabled')).toBeDefined()
    state.history.value = { ...payload(), group_uuid: 'other' }
    await flushPromises()
    resolve({ data: { availability: 'available', mode: 'submitted_state', submitted_state: [{ label: 'Old group', available: true, value: 'Stale content' }] } })
    await flushPromises()
    expect(wrapper.text()).not.toContain('Stale content')
    wrapper.unmount()
  })

  it('shows load errors and permits retry', async () => {
    api.get.mockRejectedValue(new Error('failed'))
    const { wrapper } = render()
    await wrapper.findAll('button')[0].trigger('click')
    await flushPromises()
    expect(wrapper.text()).toContain('Please retry')
    expect(wrapper.findAll('button')[0].attributes('disabled')).toBeUndefined()
    wrapper.unmount()
  })

  it.each(['ApplicationReview', 'ApplicationAdmin'])('%s mounts shared history directly below context', view => {
    const source = readFileSync(`resources/js/views/applications/${view}.vue`, 'utf8')
    expect(source).toMatch(/<SubmissionContextSummary[^>]*\/>\s*<ReviewHistory\s*\/>/)
  })

  it('uses server Review Round numbering rather than application snapshot version', () => {
    const data = payload()
    const wrapper = mount(SubmissionContextSummary, { global: { plugins: [createStore({})],
      provide: { latestSubmission: ref({ id: 2, data: { context: 'application_submission', application_snapshot_version: 99 } }),
        group: ref({}), applicationReviewHistory: { history: ref(data) } },
      stubs: { 'static-alert': { template: '<div><slot /></div>' } } } })
    expect(wrapper.text()).toContain('Review Round 2')
    expect(wrapper.text()).not.toContain('99')
    wrapper.unmount()
  })
})
