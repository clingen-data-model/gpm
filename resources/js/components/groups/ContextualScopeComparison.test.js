import { mount, flushPromises } from '@vue/test-utils'
import { ref, provide } from 'vue'
import { describe, it, expect, vi } from 'vitest'
import ScopeDescriptionForm from '../expert_panels/ScopeDescriptionForm.vue'
import RichTextEditor from '@/components/prosekit/RichTextEditor.vue'
import ScopeOfWorkRoundComparison from './ScopeOfWorkRoundComparison.vue'
import { useScopeOfWorkComparison } from '@/composables/scope_of_work_comparison'
import { api } from '@/http'
vi.mock('@/http', () => ({ api: { get: vi.fn() } }))
vi.mock('@/components/prosekit/RichTextEditor.vue', () => ({ default: { template: '<div />' } }))
const data = () => ({ mode: 'previous_review_round', changes: [{ section: 'scope_description', key: 'scope_description', before: 'Old scope', after: 'New scope' }], unavailable_sections: [], rows: {}, summary: { changed_items: 1 } })
const global = { mocks: { $store: { getters: { 'groups/currentItem': { expert_panel: { scope_description: '' } } } }, hasAnyPermission: () => false } }
describe('Contextual scope comparison', () => {
  it.each([false, true])('replaces non-editing current text with the diff (readonly=%s)', async readonly => {
    const comparison = ref(data())
    const wrapper = mount(ScopeDescriptionForm, {
      props: { editing: false, readonly },
      global: { ...global, mocks: { ...global.mocks,
        $store: { getters: { 'groups/currentItem': { expert_panel: { scope_description: 'Current description' } } } },
      }, provide: { scopeOfWorkComparisonState: { comparison } } },
    })
    expect(wrapper.findComponent(RichTextEditor).exists()).toBe(false)
    expect(wrapper.text()).not.toContain('Current description')
    expect(wrapper.get('section').text()).toContain('Changes since previous review round')
    expect(wrapper.find('del').exists()).toBe(true)
    comparison.value = { ...data(), unavailable_sections: ['scope_description'] }
    await flushPromises()
    expect(wrapper.find('section').exists()).toBe(false)
    expect(wrapper.text()).toContain('Current description')
    comparison.value = { changes: [] }
    await flushPromises()
    expect(wrapper.text()).toContain('Current description')
    wrapper.unmount()
  })

  it.each(['approved_baseline', 'previous_review_round'])('keeps the editor beside a live %s diff', mode => {
    const comparison = ref({ ...data(), source: 'live', mode })
    const wrapper = mount(ScopeDescriptionForm, { props: { editing: true, readonly: false },
      global: { ...global, provide: { scopeOfWorkComparisonState: { comparison } } } })
    expect(wrapper.findComponent(RichTextEditor).exists()).toBe(true)
    expect(wrapper.find('section').exists()).toBe(true)
    expect(wrapper.find('del').exists()).toBe(true)
    expect(wrapper.find('ins').exists()).toBe(true)
    wrapper.unmount()
  })
  it('shows saved live scope changes and does not use unsaved editor text', async () => {
    const comparison = ref({ ...data(), source: 'live', mode: 'approved_baseline' })
    const wrapper = mount(ScopeDescriptionForm, { global: { ...global, provide: { scopeOfWorkComparisonState: { comparison } } } })
    expect(wrapper.text()).toContain('Current saved draft changes; unsaved edits are not included.')
    expect(wrapper.get('section').text()).toContain('New')
    wrapper.vm.group.expert_panel.scope_description = 'Unsaved typing'
    await flushPromises()
    expect(wrapper.get('section').text()).not.toContain('Unsaved typing')
    comparison.value = { ...comparison.value, mode: 'previous_review_round' }
    await flushPromises()
    expect(wrapper.text()).toContain('Changes since last submission')
    wrapper.unmount()
  })
  it('shares one request between the centralized block and field', async () => {
    api.get.mockReset().mockResolvedValue({ data: data() })
    const Parent = {
      components: { ScopeOfWorkRoundComparison, ScopeDescriptionForm },
      setup() {
        provide('scopeOfWorkComparisonState', useScopeOfWorkComparison(() => ['group', 1]))
      },
      template: '<div><ScopeOfWorkRoundComparison group-uuid="group" :submission-id="1" /><ScopeDescriptionForm /></div>',
    }
    const wrapper = mount(Parent, { global })
    await flushPromises()
    expect(api.get).toHaveBeenCalledTimes(1)
    const field = wrapper.findComponent(ScopeDescriptionForm)
    expect(field.text()).toContain('Changes since previous review round')
    expect(field.find('del').text()).toContain('Old')
    expect(field.find('ins').text()).toContain('New')
    wrapper.unmount()
  })
  it('reacts to baseline mode and hides missing or unchanged scope data', async () => {
    const comparison = ref({ ...data(), mode: 'approved_baseline' })
    const wrapper = mount(ScopeDescriptionForm, { global: { ...global, provide: { scopeOfWorkComparisonState: { comparison } } } })
    expect(wrapper.text()).toContain('Changes since approved baseline')
    expect(wrapper.text()).toContain('Submitted changes only; unsubmitted edits are not included.')
    comparison.value = { ...data(), unavailable_sections: ['scope_description'] }
    await flushPromises()
    expect(wrapper.find('section').exists()).toBe(false)
    comparison.value = { ...data(), changes: [] }
    await flushPromises()
    expect(wrapper.find('section').exists()).toBe(false)
    wrapper.unmount()
  })
  it('renders no contextual comparison without a provider', () => {
    const wrapper = mount(ScopeDescriptionForm, { global })
    expect(wrapper.find('section').exists()).toBe(false)
    wrapper.unmount()
  })
})
