import { mount, flushPromises } from '@vue/test-utils'
import { createStore } from 'vuex'
import { describe, it, expect, vi, afterEach } from 'vitest'
import { api } from '@/http'
import ApplicationDetail from './ApplicationDetail.vue'
import { useScopeOfWorkComparison } from '@/composables/scope_of_work_comparison'

vi.mock('@/http', () => ({ api: { get: vi.fn() } }))
vi.mock('@/auth_utils.js', () => ({ hasPermission: () => true }))
vi.mock('@/composables/comment_manager.js', () => ({ default: () => ({ getComments: vi.fn(), getSummary: vi.fn(), dispose: vi.fn() }) }))
vi.mock('@/composables/scope_of_work_comparison', () => ({ useScopeOfWorkComparison: vi.fn(() => ({})) }))
vi.mock('./ApplicationAdmin.vue', () => ({ default: { name: 'ApplicationAdmin', emits: ['updated', 'saved', 'deleted'], template: '<div />' } }))
vi.mock('./ApplicationReview.vue', () => ({ default: { name: 'ApplicationReview', template: '<div />' } }))
afterEach(() => vi.restoreAllMocks())

describe('review history refresh integration', () => {
  it.each(['updated', 'saved', 'deleted'])('refreshes history after reviewer %s events', async event => {
    api.get.mockResolvedValue({ data: { cycles: [], group_uuid: 'group' } })
    const store = createStore({ getters: { 'groups/currentItemOrNew': () => ({ uuid: 'group', id: 1 }) } })
    vi.spyOn(store, 'dispatch').mockResolvedValue()
    const wrapper = mount(ApplicationDetail, { props: { uuid: 'group' }, global: { plugins: [store] } })
    await flushPromises()
    api.get.mockClear()
    wrapper.findComponent({ name: 'ApplicationAdmin' }).vm.$emit(event)
    await flushPromises()
    expect(api.get).toHaveBeenCalledWith('/api/groups/group/application/review-history')
    expect(api.get).toHaveBeenCalledWith('/api/groups/group/application/latest-submission')
    wrapper.unmount()
  })
})

it.each([null, 'scope_of_work_revision'])('limits contextual loading to SoW submissions (context=%s)', async context => {
  api.get.mockImplementation(url => Promise.resolve({ data: url.endsWith('latest-submission')
    ? { id: 42, submission_type_id: 2, data: { context } } : { cycles: [] } }))
  const store = createStore({ getters: { 'groups/currentItemOrNew': () => ({ uuid: 'group', id: 1 }) } })
  vi.spyOn(store, 'dispatch').mockResolvedValue()
  const wrapper = mount(ApplicationDetail, { props: { uuid: 'group' }, global: { plugins: [store] } })
  await flushPromises()
  expect(useScopeOfWorkComparison.mock.lastCall[0]()).toEqual(['group', context ? 42 : null])
  expect(api.get).toHaveBeenCalledWith('/api/groups/group/application/review-history')
  wrapper.unmount()
})
