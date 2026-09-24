import { mount, flushPromises } from '@vue/test-utils'
import { createStore } from 'vuex'
import { describe, it, expect, vi, afterEach } from 'vitest'
import { api } from '@/http'
import ApplicationDetail from './ApplicationDetail.vue'

vi.mock('@/http', () => ({ api: { get: vi.fn() } }))
vi.mock('@/auth_utils.js', () => ({ hasPermission: () => true }))
vi.mock('@/composables/comment_manager.js', () => ({ default: () => ({ getComments: vi.fn() }) }))
vi.mock('@/composables/scope_of_work_comparison', () => ({ useScopeOfWorkComparison: () => ({}) }))
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
