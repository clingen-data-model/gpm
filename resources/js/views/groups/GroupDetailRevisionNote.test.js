import { shallowMount, flushPromises } from '@vue/test-utils'
import { createStore } from 'vuex'
import { ref } from 'vue'
import { describe, it, expect, vi } from 'vitest'
import GroupDetail from './GroupDetail.vue'
import InitialApplicationRevisionNote from '@/components/applications/InitialApplicationRevisionNote.vue'
vi.mock('@/adapters/log_entry_repository', () => ({ logEntries: ref([]), fetchEntries: vi.fn() }))
vi.mock('@/composables/scope_of_work_comparison', () => ({ useScopeOfWorkComparison: () => ({ comparison: ref(null) }) }))
vi.mock('@/composables/use_application.js', () => ({ getApplicationForGroup: vi.fn() }))
vi.mock('@/auth_utils', () => ({ hasPermission: () => true }))
describe('Group Detail initial revision feedback', () => {
  it.each([1, 4])('shows only the reviewer note and hides it after resubmission or approval (step %s)', async step => {
    const submission = { id: 1, submission_type_id: step === 4 ? 2 : 1, submission_status_id: 2,
      response_content: 'Please revise the application', body: 'Outgoing email body' }
    const store = createStore({ state: { group: { id: 1, uuid: 'panel', is_ep: true, is_gcep: true, members: [], type: {},
      expert_panel: { current_step: step, submissions: [], genes: [] } } }, getters: { 'groups/currentItemOrNew': state => state.group } })
    const dispatch = vi.spyOn(store, 'dispatch').mockImplementation(async action => {
      if (action === 'groups/getSubmissions') store.state.group.expert_panel.submissions = [submission]
      return null
    })
    vi.spyOn(store, 'commit').mockImplementation(() => {})
    const wrapper = shallowMount(GroupDetail, { props: { uuid: 'panel' }, global: { plugins: [store],
      mocks: { $route: { meta: {} }, hasPermission: () => true, hasAnyPermission: () => true, userInGroup: () => true },
      stubs: { InitialApplicationRevisionNote: false, 'tabs-container': true, 'tab-item': true, 'modal-dialog': true, 'button-row': true, 'router-view': true, 'static-alert': true, 'edit-icon-button': true, 'screen-template': true, 'submission-wrapper': true, 'collapsible': true, 'icon-exclamation': true, 'markdown-block': true }, renderStubDefaultSlot: false,
    } })
    await flushPromises()
    expect(dispatch).toHaveBeenCalledWith('groups/getSubmissions', expect.anything())
    expect(wrapper.findComponent(InitialApplicationRevisionNote).text()).toContain('Application revisions requested')
    expect(wrapper.text()).toContain('Please revise the application')
    expect(wrapper.text()).not.toContain('Outgoing email body')
    const panel = store.state.group.expert_panel
    panel.submissions.push({ ...submission, id: 2, submission_status_id: 1 })
    await flushPromises()
    expect(wrapper.findComponent(InitialApplicationRevisionNote).text()).toBe('')
    panel.submissions = [{ ...submission, submission_status_id: 4 }]
    await flushPromises()
    expect(wrapper.findComponent(InitialApplicationRevisionNote).text()).toBe('')
    panel.submissions = [{ ...submission, response_content: null }]
    await flushPromises()
    expect(wrapper.findComponent(InitialApplicationRevisionNote).text()).toBe('')
    panel.submissions = [{ ...submission, data: { context: 'scope_of_work_revision' }, scope_of_work_version_id: 3 }]
    await flushPromises()
    expect(wrapper.findComponent(InitialApplicationRevisionNote).text()).toBe('')
    wrapper.unmount()
  })
})
