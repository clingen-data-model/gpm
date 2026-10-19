import { mount, flushPromises } from '@vue/test-utils'
import { ref } from 'vue'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { api } from '@/http'
import { useScopeOfWorkComparison } from './scope_of_work_comparison'

vi.mock('@/http', () => ({ api: { get: vi.fn() } }))

function harness(getIdentifiers) {
  let state
  const wrapper = mount({
    setup() { state = useScopeOfWorkComparison(getIdentifiers); return {} },
    template: '<div />',
  })
  return { wrapper, state }
}

describe('comparison sources', () => {
  beforeEach(() => api.get.mockReset())

  it.each(['submitted_state', 'previous_review_round'])('loads initial %s through frozen history detail', async mode => {
    const detail = { step: 1, mode, availability: 'available', comparison: { rows: { genes: [] }, changes: [] } }
    api.get.mockResolvedValue({ data: detail })
    const { wrapper, state } = harness(() => ['group', 42, 'initial'])
    await flushPromises()
    expect(api.get).toHaveBeenCalledWith('/api/groups/group/application/review-history/42')
    expect(state.comparison.value).toMatchObject({ source: 'submitted', mode, review_detail: detail })
    wrapper.unmount()
  })

  it('reloads saved drafts with the same UUID when status refreshes', async () => {
    const status = ref({ active_revision: { uuid: 'revision' } })
    api.get.mockResolvedValueOnce({ data: { source: 'live', changes: ['first save'] } })
      .mockResolvedValueOnce({ data: { source: 'live', changes: ['second save'] } })
    const { wrapper, state } = harness(() => ['group', status.value.active_revision.uuid, 'live', status.value])
    await flushPromises()
    expect(state.comparison.value.changes).toEqual(['first save'])
    status.value = { active_revision: { uuid: 'revision' } }
    await flushPromises()
    expect(api.get).toHaveBeenCalledTimes(2)
    expect(api.get).toHaveBeenLastCalledWith('/api/groups/group/scope-of-work/revisions/revision/comparison')
    expect(state.comparison.value.changes).toEqual(['second save'])
    wrapper.unmount()
  })

  it('keeps retry separate from source for existing reviewer callers', async () => {
    api.get.mockRejectedValueOnce(new Error('offline')).mockResolvedValueOnce({ data: { mode: 'approved_baseline' } })
    const { wrapper, state } = harness(() => ['group', 42])
    await flushPromises()
    expect(state.error.value).toContain('submitted comparison')
    state.retry.value++
    await flushPromises()
    expect(api.get.mock.calls.map(([url]) => url)).toEqual(Array(2).fill('/api/groups/group/application/submission/42/scope-of-work/comparison'))
    expect(state.comparison.value.mode).toBe('approved_baseline')
    wrapper.unmount()
  })

  it('ignores an outstanding live response after switching to a frozen submission', async () => {
    let resolveLive
    api.get.mockImplementationOnce(() => new Promise(resolve => { resolveLive = resolve }))
      .mockResolvedValueOnce({ data: { changes: ['frozen'] } })
    const identifiers = ref(['group', 'revision', 'live'])
    const { wrapper, state } = harness(() => identifiers.value)
    identifiers.value = ['group', 42, 'submitted']
    await flushPromises()
    resolveLive({ data: { changes: ['live'] } })
    await flushPromises()
    expect(state.comparison.value.changes).toEqual(['frozen'])
    expect(api.get).toHaveBeenLastCalledWith('/api/groups/group/application/submission/42/scope-of-work/comparison')
    wrapper.unmount()
  })
})
