import { mount, flushPromises } from '@vue/test-utils'
import { createStore } from 'vuex'
import { createRouter, createMemoryHistory } from 'vue-router'
import { describe, it, expect, vi, beforeEach } from 'vitest'
import groups from '@/store/groups'
import { api } from '@/http'
import CspecSummary from '@/components/expert_panels/CspecSummary.vue'
import StepTabs from './StepTabs.vue'
import TabsContainer from '@/components/TabsContainer.vue'
import TabItem from '@/components/TabItem.vue'
vi.mock('@/http', () => ({ api: { get: vi.fn(), postForm: vi.fn() }, queryStringFromParams: () => '' }))
const data = (step, type = 2) => ({ id: 1, uuid: 'panel', group_type_id: type === 1 ? 3 : 4, is_vcep_or_scvcep: type !== 1,
  expert_panel: { id: 1, current_step: step, expert_panel_type_id: type, is_gcep: type === 1, specifications: [] } })
const empty = { template: '<div />' }
const specs = { components: { CspecSummary }, template: '<CspecSummary />' }
async function setup(type = 2) {
  const store = createStore({ modules: { groups } })
  store.commit('groups/addItem', data(1, type))
  store.commit('groups/setCurrentItemIndexByUuid', 'panel')
  const router = createRouter({ history: createMemoryHistory(), routes: [{ path: '/', component: empty }] })
  await router.push('/')
  const wrapper = mount(StepTabs, { global: { plugins: [store, router], components: { TabsContainer, TabItem },
    stubs: { StepOne: empty, StepTwo: specs, StepThree: specs, StepFour: empty, note: true, 'icon-arrow-right': true } } })
  await flushPromises()
  return { store, wrapper }
}
describe('approval refresh settles', () => {
  beforeEach(() => { api.get.mockReset(); api.postForm.mockReset() })
  it.each([2, 3])('retains the four application tabs for panel type %s', async type => {
    api.get.mockResolvedValue({ data: [] })
    const { wrapper } = await setup(type)
    expect(wrapper.findAll('li.tab').map(tab => tab.text())).toEqual([
      'Group Definition', 'Draft Specifications', 'Pilot Specifications', 'Sustained Curation',
    ])
    await wrapper.findAll('li.tab')[3].trigger('click')
    await flushPromises()
    expect(wrapper.vm.activeStep).toBe(4)
    wrapper.unmount()
  })
  it('advances steps 1 through 4 and permits immediate navigation without repeated fetches', async () => {
    let nextStep = 2
    api.postForm.mockResolvedValue({})
    api.get.mockImplementation(url => Promise.resolve({ data: url.endsWith('/specifications') ? [] : { data: data(nextStep) } }))
    const { store, wrapper } = await setup()
    for (const step of [1, 2, 3, 4]) {
      nextStep = Math.min(step + 1, 4)
      api.get.mockClear()
      await store.dispatch('groups/approveCurrentStep', { group: store.getters['groups/currentItem'], attachments: [] })
      await flushPromises()
      expect(wrapper.vm.activeStep).toBe(nextStep)
      expect(api.get.mock.calls.filter(([url]) => url === '/api/groups/panel')).toHaveLength(1)
      const count = api.get.mock.calls.length
      await flushPromises()
      expect(api.get).toHaveBeenCalledTimes(count)
    }
    await wrapper.findAll('li.tab')[1].trigger('click')
    await flushPromises()
    expect(wrapper.vm.activeIndex).toBe(1)
    expect(wrapper.findComponent(CspecSummary).exists()).toBe(true)
    wrapper.unmount()
  })
  it('does not restore an old group when a specification response arrives after approval', async () => {
    let resolveSpecs
    const store = createStore({ modules: { groups } })
    store.commit('groups/addItem', data(1))
    store.commit('groups/setCurrentItemIndexByUuid', 'panel')
    api.get.mockImplementationOnce(() => new Promise(resolve => { resolveSpecs = resolve }))
    // Use a variant panel for the actual specifications action.
    store.commit('groups/addItem', data(1))
    const oldGroup = store.getters['groups/currentItem']
    const request = store.dispatch('groups/getSpecifications', oldGroup)
    store.commit('groups/addItem', data(2))
    resolveSpecs({ data: [{ id: 3, name: 'Captured specs' }] })
    await request
    expect(store.getters['groups/currentItem'].expert_panel.current_step).toBe(2)
    expect(store.getters['groups/currentItem'].expert_panel.specifications[0].id).toBe(3)
  })
  it('keeps GCEP on Step 1 and awaits the approval refresh before completing', async () => {
    const { store, wrapper } = await setup(1)
    let resolveRefresh
    api.postForm.mockResolvedValue({})
    api.get.mockImplementationOnce(() => new Promise(resolve => { resolveRefresh = resolve }))
    let finished = false
    const approval = store.dispatch('groups/approveCurrentStep', { group: store.getters['groups/currentItem'], attachments: [] }).then(() => { finished = true })
    await flushPromises()
    expect(finished).toBe(false)
    resolveRefresh({ data: { data: data(1, 1) } })
    await approval
    expect(finished).toBe(true)
    expect(wrapper.vm.activeStep).toBe(1)
    expect(wrapper.findComponent(TabsContainer).exists()).toBe(false)
    wrapper.unmount()
  })
})
