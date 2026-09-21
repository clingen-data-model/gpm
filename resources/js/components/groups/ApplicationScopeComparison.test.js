import { mount, flushPromises } from '@vue/test-utils'
import { ref } from 'vue'
import { createStore } from 'vuex'
import { describe, it, expect } from 'vitest'
import DefinitionReview from '../expert_panels/DefinitionReview.vue'
const data = () => ({ mode: 'approved_baseline', changes: [{ section: 'scope_description', before: 'Old scope', after: '<img> scope' }] })
const options = comparison => ({ global: {
  plugins: [createStore({ getters: { 'groups/currentItemOrNew': () => ({ type: {}, expert_panel: { scope_description: 'Current scope' }, members: [] }) } })],
  provide: comparison ? { scopeOfWorkComparisonState: { comparison } } : {},
  stubs: { ReviewSection: { template: '<div><slot /></div>' }, ReviewMembership: true, ObjectDictionary: true, DictionaryRow: { template: '<div><slot /></div>' }, GeneCurationStatus: true, VcepGeneList: true },
} })
describe('Definition review contextual scope', () => {
  it('replaces the current description with escaped submitted changes and reacts to mode', async () => {
    const comparison = ref(data())
    const wrapper = mount(DefinitionReview, options(comparison))
    expect(wrapper.text()).not.toContain('Current scope')
    expect(wrapper.text()).toContain('Changes since approved baseline')
    expect(wrapper.text()).toContain('Submitted changes only; unsubmitted edits are not included.')
    expect(wrapper.get('del').text()).toContain('Old')
    expect(wrapper.get('ins').text()).toContain('<img>')
    expect(wrapper.find('img').exists()).toBe(false)
    comparison.value = { ...data(), mode: 'previous_review_round' }
    await flushPromises()
    expect(wrapper.text()).toContain('Changes since previous review round')
    comparison.value = { ...data(), unavailable_sections: ['scope_description'] }
    await flushPromises()
    expect(wrapper.find('section').exists()).toBe(false)
    expect(wrapper.text()).toContain('Current scope')
    comparison.value = { changes: [] }
    await flushPromises()
    expect(wrapper.find('section').exists()).toBe(false)
    expect(wrapper.text()).toContain('Current scope')
    wrapper.unmount()
  })
  it('keeps normal review unchanged without a provider', () => {
    const wrapper = mount(DefinitionReview, options())
    expect(wrapper.find('section').exists()).toBe(false)
    expect(wrapper.text()).toContain('Current scope')
    wrapper.unmount()
  })
})
