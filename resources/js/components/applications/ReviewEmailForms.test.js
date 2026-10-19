import { mount, flushPromises } from '@vue/test-utils'
import { createStore } from 'vuex'
import { beforeEach, describe, it, expect, vi } from 'vitest'
import RejectStepForm from './RejectStepForm.vue'
import ApproveStepForm from './ApproveStepForm.vue'
import ScopeOfWorkApproveForm from './ScopeOfWorkApproveForm.vue'
import { api } from '@/http'

vi.mock('@/http', () => ({ api: { post: vi.fn(), get: vi.fn() } }))
vi.mock('@/components/forms/UserDefinedMailForm.vue', () => ({ default: {
  name: 'UserDefinedMailForm', props: ['modelValue'], emits: ['update:modelValue'], template: '<div />',
} }))
const group = { uuid: 'panel', expert_panel: { current_step: 1, hasPendingSubmissionForCurrentStep: true } }
const note = 'Required Revisions:\n- Clarify scope\n\nSuggestions:\n- Clarify membership'
const stubs = {
  'form-container': { template: '<div><slot /></div>' },
  'dictionary-row': { template: '<div><slot /></div>' },
  'button-row': { name: 'button-row', template: '<div><slot /></div>', emits: ['submitted', 'canceled'] },
  'input-row': { name: 'input-row', props: ['modelValue'], emits: ['update:modelValue'], template: '<div />' },
  'static-alert': true,
}
beforeEach(() => {
  vi.clearAllMocks()
  api.get.mockResolvedValue({ data: { subject: 'Draft', body: 'Email template', reviewer_note: note } })
  api.post.mockResolvedValue({})
})

describe('request revision initialization', () => {
  it.each([[false, 1], [false, 2], [true, 1]])('initializes once and keeps note and email edits independent (SoW=%s, type=%s)', async (sow, type) => {
    const wrapper = mount(RejectStepForm, { props: { group,
      submission: { id: 42, submission_type_id: type, data: sow ? { context: 'scope_of_work_revision' } : {} },
    }, global: { stubs } })
    await wrapper.vm.getEmailTemplate()
    const input = wrapper.findComponent({ name: 'input-row' })
    expect(input.props('modelValue')).toBe(note)
    input.vm.$emit('update:modelValue', 'Reviewer edited note')
    wrapper.findComponent({ name: 'UserDefinedMailForm' }).vm.$emit('update:modelValue', { subject: 'Edited', body: 'Edited email' })
    await flushPromises()
    expect(input.props('modelValue')).toBe('Reviewer edited note')
    wrapper.findComponent({ name: 'button-row' }).vm.$emit('submitted')
    await flushPromises()
    expect(api.post).toHaveBeenCalledWith('/api/groups/panel/application/submission/42/rejection', expect.objectContaining({
      response_content: 'Reviewer edited note', body: 'Edited email', subject: 'Edited',
    }))
    await wrapper.vm.getEmailTemplate()
    input.vm.$emit('update:modelValue', '')
    await wrapper.get('input[type="checkbox"]').setValue(false)
    await wrapper.get('input[type="checkbox"]').setValue(true)
    await flushPromises()
    expect(input.props('modelValue')).toBe('')
    wrapper.unmount()
  })
})

describe('shared approval email option', () => {
  it('blocks approval if loading the editable draft fails and permits retry', async () => {
    api.get.mockRejectedValueOnce(new Error('offline'))
    const wrapper = mount(ScopeOfWorkApproveForm, { props: { group, submission: { id: 42 } }, global: { stubs } })
    await wrapper.get('input[type="checkbox"]').setValue(true)
    await flushPromises()
    expect(wrapper.text()).toContain('The approval email could not be loaded.')
    expect(wrapper.get('button.blue').element.disabled).toBe(true)
    expect(api.post).not.toHaveBeenCalled()
    await wrapper.findAll('button').find(button => button.text() === 'Retry').trigger('click')
    await flushPromises()
    expect(wrapper.get('button.blue').element.disabled).toBe(false)
    expect(wrapper.findComponent({ name: 'UserDefinedMailForm' }).exists()).toBe(true)
    wrapper.unmount()
  })
  it.each([ApproveStepForm, ScopeOfWorkApproveForm])('offers editable email for $name', async Component => {
    const store = createStore({ getters: { 'groups/currentItemOrNew': () => group } })
    const dispatch = vi.spyOn(store, 'dispatch').mockResolvedValue({})
    const wrapper = mount(Component, { props: Component === ScopeOfWorkApproveForm ? {
      group, submission: { id: 42, data: { approval_step: 1 } },
    } : {}, global: { stubs, plugins: [store] } })
    expect(wrapper.text()).toContain('Send notification email to contacts')
    expect(wrapper.findComponent({ name: 'UserDefinedMailForm' }).exists()).toBe(false)
    await wrapper.get('input[type="checkbox"]').setValue(true)
    await flushPromises()
    const editor = wrapper.findComponent({ name: 'UserDefinedMailForm' })
    expect(editor.props('modelValue').body).toBe('Email template')
    editor.vm.$emit('update:modelValue', { subject: 'Approval edit', body: 'Reviewer email edit', files: [] })
    await flushPromises()
    await wrapper.get('button.blue').trigger('click')
    await flushPromises()
    if (Component === ScopeOfWorkApproveForm) {
      const data = api.post.mock.calls[0][1]
      expect(data.get('notify_contacts')).toBe('1')
      expect(data.get('body')).toBe('Reviewer email edit')
    } else {
      expect(dispatch).toHaveBeenCalledWith('groups/approveCurrentStep', expect.objectContaining({ notifyContacts: true, body: 'Reviewer email edit' }))
    }
    wrapper.unmount()
  })
  it('posts no approval email when unchecked', async () => {
    const wrapper = mount(ScopeOfWorkApproveForm, { props: { group, submission: { id: 42 } }, global: { stubs } })
    await wrapper.get('button.blue').trigger('click')
    await flushPromises()
    expect(api.get).not.toHaveBeenCalled()
    expect(api.post.mock.calls[0][1].get('notify_contacts')).toBe('0')
    expect(api.post.mock.calls[0][1].has('body')).toBe(false)
  })
})
