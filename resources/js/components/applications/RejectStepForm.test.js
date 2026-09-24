import { mount, flushPromises } from '@vue/test-utils'
import { describe, it, expect, vi, afterEach } from 'vitest'
import { api } from '@/http'
import RejectStepForm from './RejectStepForm.vue'
import UserDefinedMailForm from '@/components/forms/UserDefinedMailForm.vue'
import SubmissionInfo from './SubmissionInfo.vue'
import InitialApplicationRevisionNote from './InitialApplicationRevisionNote.vue'

vi.mock('@/http', () => ({ api: { get: vi.fn(), post: vi.fn() } }))
afterEach(() => vi.clearAllMocks())

function render(submission) {
  return mount(RejectStepForm, { props: { group: { uuid: 'group' }, submission }, global: { stubs: {
    'form-container': { template: '<div><slot /></div>' },
    'dictionary-row': { template: '<div><slot /></div>' },
    'input-row': { props: ['modelValue'], emits: ['update:modelValue'], template: '<textarea :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" />' },
    'button-row': { emits: ['submitted'], template: '<button @click="$emit(\'submitted\')">Request revisions</button>' },
    UserDefinedMailForm: true,
  } } })
}

describe('revision request note and email', () => {
  it.each([
    { id: 1, submission_type_id: 1, data: { context: 'application_submission' } },
    { id: 2, submission_type_id: 1, data: null },
    { id: 3, submission_type_id: 1, data: { context: 'scope_of_work_revision' } },
  ])('sends an independent reviewer note and editable email for submission $id', async submission => {
    api.post.mockResolvedValue({})
    const wrapper = render(submission)
    await wrapper.get('textarea').setValue('Concise reviewer note')
    wrapper.findComponent(UserDefinedMailForm).vm.$emit('update:modelValue', { subject: 'Editable subject', body: 'Edited email body' })
    await wrapper.get('button').trigger('click')
    await flushPromises()
    expect(api.post).toHaveBeenCalledWith(`/api/groups/group/application/submission/${submission.id}/rejection`, {
      notify_contacts: true, subject: 'Editable subject', body: 'Edited email body', response_content: 'Concise reviewer note',
    })
    expect(wrapper.emitted('saved')).toHaveLength(1)
    expect(wrapper.get('textarea').element.value).toBe('')
    wrapper.unmount()
  })

  it('supports a reviewer note without sending email', async () => {
    api.post.mockResolvedValue({})
    const wrapper = render({ id: 1, submission_type_id: 1 })
    await wrapper.get('textarea').setValue('Please clarify the scope')
    await wrapper.get('input[type=checkbox]').setValue(false)
    await wrapper.get('button').trigger('click')
    await flushPromises()
    expect(api.post.mock.calls[0][1]).toMatchObject({ notify_contacts: false, response_content: 'Please clarify the scope' })
    wrapper.unmount()
  })

  it('keeps the initial Step 4 form unchanged', () => {
    const wrapper = render({ id: 1, submission_type_id: 2 })
    expect(wrapper.find('textarea').exists()).toBe(false)
    wrapper.unmount()
  })

  it('shows submitters the saved reviewer note as escaped text', () => {
    const wrapper = mount(SubmissionInfo, { props: { submission: {
      id: 1, submission_type_id: 1, submission_status_id: 2, response_content: 'Review <script>scope</script>',
    } }, global: { stubs: { badge: true, 'static-alert': { template: '<div><slot /></div>' } } } })
    expect(wrapper.text()).toContain('Revision request notes: Review <script>scope</script>')
    expect(wrapper.find('script').exists()).toBe(false)
    wrapper.unmount()
  })

  it('displays the latest initial reviewer note on the submitter application and clears it after resubmission', async () => {
    const submission = { id: 1, submission_type_id: 1, submission_status_id: 2, response_content: 'Clarify your scope' }
    const wrapper = mount(InitialApplicationRevisionNote, { props: { application: { current_step: 1, submissions: [submission] } } })
    expect(wrapper.text()).toContain('Reviewer note: Clarify your scope')
    await wrapper.setProps({ application: { current_step: 1, submissions: [submission, { ...submission, id: 2, submission_status_id: 1 }] } })
    expect(wrapper.text()).toBe('')
    await wrapper.setProps({ application: { current_step: 4, submissions: [submission] } })
    expect(wrapper.text()).toBe('')
    wrapper.unmount()
  })
})
