import { mount, flushPromises } from '@vue/test-utils'
import { createStore } from 'vuex'
import { describe, it, expect, vi } from 'vitest'
import AttestationReanalysis from './AttestationReanalysis.vue'
import api from '@/http/api'
vi.mock('@/http/api', () => ({ default: { post: vi.fn().mockResolvedValue({}) } }))
const checkbox = { props: ['modelValue'], emits: ['update:modelValue'], template: `<label><input type="checkbox" :checked="modelValue" @change="$emit('update:modelValue', $event.target.checked)"><slot /></label>` }
function render(explanation = null) {
  const store = createStore({ state: { group: { uuid: 'sc', is_vcep: false, type: { display_name: 'SC-VCEP' }, expert_panel: {
    reanalysis_other: explanation, reanalysis_conflicting: false, reanalysis_review_lp: false, reanalysis_review_lb: false,
    reanalysis_attestation_date: explanation ? '2026-10-01' : null,
  } } }, getters: { 'groups/currentItemOrNew': state => state.group } })
  const wrapper = mount(AttestationReanalysis, { global: { plugins: [store], stubs: { checkbox, 'input-row': { template: '<div><slot /></div>' } } } })
  return { wrapper, panel: store.state.group.expert_panel }
}
describe('SC-VCEP plans differ', () => {
  it('saves, hydrates and edits the explanation without clearing it on mount', async () => {
    let { wrapper, panel } = render()
    await wrapper.findAll('input')[3].setValue(true)
    await wrapper.get('textarea').setValue('Alternate review schedule')
    expect(wrapper.emitted('update')).toBeTruthy()
    expect(panel.reanalysis_attestation_date).toBeTruthy()
    await wrapper.vm.save()
    expect(api.post).toHaveBeenLastCalledWith('/api/groups/sc/application/attestations/reanalysis', expect.objectContaining({ reanalysis_other: 'Alternate review schedule' }))
    wrapper.unmount()
    ;({ wrapper, panel } = render('Alternate review schedule'))
    expect(wrapper.findAll('input')[3].element.checked).toBe(true)
    expect(wrapper.get('textarea').element.value).toBe('Alternate review schedule')
    expect(panel.reanalysis_attestation_date).toBe('2026-10-01')
    await wrapper.get('textarea').setValue('Updated review schedule')
    await wrapper.vm.save()
    expect(api.post.mock.lastCall[1].reanalysis_other).toBe('Updated review schedule')
    await wrapper.findAll('input')[3].setValue(false)
    await flushPromises()
    expect(panel.reanalysis_other).toBeNull()
    expect(panel.reanalysis_attestation_date).toBeNull()
    expect(wrapper.find('textarea').exists()).toBe(false)
    wrapper.unmount()
  })
})
