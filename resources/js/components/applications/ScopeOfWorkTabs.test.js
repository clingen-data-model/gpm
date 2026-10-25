import { mount, flushPromises } from '@vue/test-utils'
import { reactive, ref } from 'vue'
import { createStore } from 'vuex'
import { createRouter, createMemoryHistory } from 'vue-router'
import { describe, it, expect, vi } from 'vitest'
import StepTabs from './StepTabs.vue'
import TabsContainer from '@/components/TabsContainer.vue'
import TabItem from '@/components/TabItem.vue'
import ApplicationAdmin from '@/views/applications/ApplicationAdmin.vue'
import ApplicationReview from '@/views/applications/ApplicationReview.vue'
import { activeScopeOfWorkReviewSubmission } from '@/domain/review_submission'

vi.mock('@/auth_utils.js', () => ({ hasPermission: () => false, hasAnyPermission: () => false }))
const empty = { template: '<div />' }
const labels = ['Group Definition', 'Draft Specifications', 'Pilot Specifications', 'Sustained Curation']
const revision = (step, extra = {}) => ({ id: 10, group_id: 1, submission_status_id: 1,
  data: { context: 'scope_of_work_revision', approval_step: step }, ...extra })
async function setup(type, latestValue, component = StepTabs, query = { tab: 'pilot specifications' }) {
  const group = reactive({ id: 1, uuid: 'one', is_gcep: type === 1, hasContacts: true,
    expert_panel: { expert_panel_type_id: type, is_gcep: type === 1, current_step: 5, submissions: [] } })
  const latest = ref(latestValue)
  const selected = vi.fn()
  const store = createStore({ getters: { 'groups/currentItemOrNew': () => group } })
  const router = createRouter({ history: createMemoryHistory(), routes: [{ path: '/:uuid', component: empty }] })
  await router.push({ path: '/one', query })
  const wrapper = mount(component, { global: { plugins: [store, router], components: { TabsContainer, TabItem },
    provide: { latestSubmission: latest, selectReviewStep: selected, commentManager: null },
    mocks: { hasAnyPermission: () => false }, stubs: {
      StepOne: { template: '<div>Definition content</div>' }, StepTwo: empty, StepThree: empty,
      StepFour: { template: '<div>Sustained content</div>' },
      ScreenTemplate: { template: '<div><slot /></div>' }, ScopeOfWorkNameComparison: empty,
      BasicInfoData: empty, NextActions: empty, ProgressChart: empty, ApplicationLog: empty,
      SubmissionContextSummary: empty, ApplicationReviewSummary: empty, ReviewHistory: empty,
      ChairApproverControls: empty, 'modal-dialog': empty, 'static-alert': empty, note: empty,
    } } })
  await flushPromises()
  return { wrapper, group, latest, router, selected }
}
describe.each([2, 3])('panel type %s Scope of Work tab initialization', type => {
  it.each([1, 4])('opens Step %s with all four tabs despite Completed and stale route query', async step => {
    const { wrapper, group, selected, router } = await setup(type, revision(step))
    expect(wrapper.findAll('li.tab').map(tab => tab.text())).toEqual(labels)
    expect(wrapper.get('li.active').text()).toBe(labels[step - 1])
    expect(selected).toHaveBeenLastCalledWith(step)
    expect(group.expert_panel.current_step).toBe(5)
    await wrapper.findAll('li.tab')[1].trigger('click')
    await flushPromises()
    expect(wrapper.get('li.active').text()).toBe(labels[1])
    // A refresh of the same round must preserve the user's navigation.
    group.expert_panel.current_step = 4
    await flushPromises()
    expect(wrapper.get('li.active').text()).toBe(labels[1])
    await router.replace({ query: { tab: 'sustained curation' } })
    expect(wrapper.get('li.active').text()).toBe(labels[1])
    wrapper.unmount()
  })
  it.each([{}, { approval_step: 3 }, { approval_step: 4, conflicting: true }])('falls back to Definition for unresolved metadata %o', async data => {
    const { wrapper } = await setup(type, revision(null, { data: { context: 'scope_of_work_revision', ...data },
      ...(data.conflicting ? { submission_type_id: 1 } : {}) }))
    expect(wrapper.get('li.active').text()).toBe(labels[0])
    wrapper.unmount()
  })
  it('resolves submission type and responds to late metadata and application changes', async () => {
    const { wrapper, latest, group } = await setup(type, null)
    latest.value = revision(null, { submission_type_id: 2 })
    await flushPromises()
    expect(wrapper.get('li.active').text()).toBe(labels[3])
    await wrapper.findAll('li.tab')[2].trigger('click')
    group.uuid = 'two'
    group.id = 2
    group.expert_panel.submissions = [revision(1, { id: 20, group_id: 2 })]
    latest.value = null
    await flushPromises()
    expect(wrapper.get('li.active').text()).toBe(labels[0])
    expect(wrapper.findAll('li.active')).toHaveLength(1)
    wrapper.unmount()
  })
  it.each([ApplicationAdmin, ApplicationReview])('retains real step tabs in the page instead of a single-step workaround', async component => {
    const { wrapper } = await setup(type, revision(4), component, { tab: 'application log' })
    const tabs = wrapper.getComponent(StepTabs)
    expect(tabs.findAll('li.tab').map(tab => tab.text())).toEqual(labels)
    expect(tabs.get('li.active').text()).toBe(labels[3])
    wrapper.unmount()
  })
})
it('keeps normal application route selection and current-step updates', async () => {
  const { wrapper, group } = await setup(2, { id: 2, data: { context: 'application_submission' } })
  expect(wrapper.get('li.active').text()).toBe(labels[2])
  group.expert_panel.current_step = 4
  await flushPromises()
  expect(wrapper.get('li.active').text()).toBe(labels[3])
  wrapper.unmount()
})
it('keeps GCEP on its single Definition step', async () => {
  const { wrapper, selected } = await setup(1, revision(4))
  expect(wrapper.findComponent(TabsContainer).exists()).toBe(false)
  expect(wrapper.text()).toContain('Definition content')
  expect(selected).toHaveBeenLastCalledWith(1)
  wrapper.unmount()
})
it('does not select an old revisions-requested round after approval', () => {
  const group = { expert_panel: { submissions: [revision(1, { submission_status_id: 2 }),
    revision(4, { id: 11, submission_status_id: 4 })] } }
  expect(activeScopeOfWorkReviewSubmission(group)).toBeNull()
})
