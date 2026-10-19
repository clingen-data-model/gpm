import { mount, flushPromises } from '@vue/test-utils'
import { describe, it, expect, vi } from 'vitest'
import RejectStepForm from '../RejectStepForm.vue'
import { api } from '@/http'
vi.mock('@/http', () => ({ api: { post: vi.fn().mockResolvedValue({}), get: vi.fn() } }))
describe('existing Request Revision behavior', () => {
  it.each([1, 2])('preserves notes and submission endpoint for submission type %s', async type => {
    const wrapper = mount(RejectStepForm, { props: { group: { uuid: 'panel' }, submission: { id: 42, submission_type_id: type } },
      global: { stubs: { 'form-container': { template: '<div><slot /></div>' },
        'dictionary-row': { template: '<div><slot /></div>' }, 'input-row': true,
        'button-row': true, UserDefinedMailForm: true } } })
    const notes = wrapper.findComponent({ name: 'input-row' })
    expect(notes.exists()).toBe(true)
    if (notes.exists()) notes.vm.$emit('update:modelValue', 'Please revise scope')
    await wrapper.get('input[type="checkbox"]').setValue(false)
    wrapper.findComponent({ name: 'button-row' }).vm.$emit('submitted')
    await flushPromises()
    expect(api.post).toHaveBeenLastCalledWith('/api/groups/panel/application/submission/42/rejection',
      expect.objectContaining({ notify_contacts: false, response_content: 'Please revise scope' }))
    expect(wrapper.emitted('saved')).toHaveLength(1)
  })
})
