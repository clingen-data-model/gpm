import { mount, flushPromises } from '@vue/test-utils';
import { vi, describe, it, expect, beforeEach } from 'vitest';
import InactivationRequest from '@/components/groups/InactivationRequest.vue';
import { api } from '@/http';
import SearchSelect from '@/components/forms/SearchSelect.vue';
import ContactListSelector from '@/components/forms/ContactListSelector.vue';
import ModalDialog from '@/components/ModalDialog.vue';

vi.mock('@/http', () => ({ api: { get: vi.fn(), post: vi.fn(), put: vi.fn(), delete: vi.fn() } }));
const draft = () => ({
  uuid: 'request', status: 'draft', version: 1, can_edit: true, can_delete: true, documents: [],
  answers: { group_name: 'Test panel', affiliation_id: '123', parent_name: '', request_date: '2026-11-10',
    liaisons: [], transfer_planned: 'yes', receiving_group_id: null,
    receiving_name: '', receiving_liaisons: [], transfer_attestation: false, no_transfer_attestation: false,
    public_text: '', materials_access: '', publications: [], continuing_members: [] },
});
const options = { members: [{ id: 7, member_id: 7, person_id: 17, name: 'Test Liaison', role: 'Grant Liaison', email: 'liaison@example.org' }], receiving_groups: [{ id: 9, name: 'Receiving EP', affiliation_id: '50049', parent_name: 'Receiving CDWG' }], receiving_members: [], receiving_liaison_suggestions: [] };
const button = (wrapper, text) => wrapper.findAll('button').find(b => b.text() === text);
async function render(data = draft(), isGcep = true) {
  api.get.mockImplementation(url => Promise.resolve({ data: url.endsWith('/options') ? options : { data, can_start: true } }));
  const wrapper = mount(InactivationRequest, { props: { group: { uuid: 'group', is_gcep: isGcep } } });
  await flushPromises();
  return wrapper;
}
beforeEach(() => vi.resetAllMocks());

describe('Inactivation request', () => {
  it('starts a draft only when requested and populates a selected liaison', async () => {
    const wrapper = await render(null);
    expect(api.post).not.toHaveBeenCalled();
    api.post.mockResolvedValue({ data: { data: draft() } });
    await button(wrapper, 'Start Inactivation Request').trigger('click');
    await flushPromises();
    expect(wrapper.find('fieldset').attributes()).not.toHaveProperty('disabled');
    expect(button(wrapper, 'Save draft').attributes()).not.toHaveProperty('disabled');
    wrapper.findComponent(SearchSelect).vm.setSelection(options.members[0]);
    await flushPromises();
    expect(wrapper.find('table').text()).toContain('Test Liaison');
    expect(wrapper.find('table').text()).toContain('Grant Liaison');
    expect(wrapper.find('table').text()).toContain('liaison@example.org');
    wrapper.unmount();
  });

  it('saves edits before submission and locks the submitted form', async () => {
    const wrapper = await render();
    await wrapper.find('input').setValue('Updated name');
    api.put.mockImplementation((url, payload) => Promise.resolve({ data: { data: { ...draft(), answers: payload.answers, version: 2 } } }));
    api.post.mockResolvedValue({ data: { data: { ...draft(), status: 'submitted', can_edit: false, version: 3 } } });
    await button(wrapper, 'G. Additional opportunities').trigger('click');
    await button(wrapper, 'Submit request').trigger('click');
    await button(wrapper, 'Confirm submission').trigger('click');
    await flushPromises();
    expect(api.put.mock.calls[0][1].answers.group_name).toBe('Updated name');
    expect(api.post).toHaveBeenCalledWith('/api/groups/group/inactivation-request/submit', { version: 2 });
    expect(wrapper.text()).toContain('This request is read-only.');
    expect(button(wrapper, 'Save draft')).toBeUndefined();
    expect(wrapper.find('fieldset').attributes()).toHaveProperty('disabled');
    wrapper.unmount();
  });

  it('shows no-transfer acknowledgement only for variant panels', async () => {
    const wrapper = await render(draft(), false);
    await button(wrapper, 'Next').trigger('click');
    await wrapper.find('select').setValue('no');
    expect(wrapper.text()).toContain('downgraded from 3 stars to 1 star');
    expect(wrapper.text()).not.toContain('Add receiving liaison');
    wrapper.unmount();
  });

  it('retains edits and displays submission validation errors', async () => {
    const wrapper = await render();
    api.put.mockResolvedValue({ data: { data: { ...draft(), version: 2 } } });
    api.post.mockRejectedValue({ response: { data: { errors: { final_report: ['Upload a report.'] } } } });
    await button(wrapper, 'G. Additional opportunities').trigger('click');
    await button(wrapper, 'Submit request').trigger('click');
    await button(wrapper, 'Confirm submission').trigger('click');
    await flushPromises();
    expect(wrapper.find('[role="alert"]').text()).toContain('Upload a report.');
    expect(button(wrapper, 'Save draft')).toBeDefined();
    wrapper.unmount();
  });

  it('keeps approved requests visible and read-only', async () => {
    const wrapper = await render({ ...draft(), status: 'approved', can_edit: false });
    expect(wrapper.text()).toContain('Status: Approved');
    expect(button(wrapper, 'Start Inactivation Request')).toBeUndefined();
    expect(api.get).toHaveBeenCalledTimes(1);
    wrapper.unmount();
  });

  it('has a distinct active section and supports Previous/Next', async () => {
    const wrapper = await render();
    expect(button(wrapper, 'A. Contact information').classes()).toContain('section-active');
    await button(wrapper, 'Next').trigger('click');
    expect(button(wrapper, 'B. Transfer panel').attributes('aria-current')).toBe('step');
    expect(button(wrapper, 'A. Contact information').classes()).not.toContain('section-active');
    await button(wrapper, 'Previous').trigger('click');
    expect(button(wrapper, 'A. Contact information').attributes('aria-current')).toBe('step');
    wrapper.unmount();
  });

  it('prefills receiving details once and preserves contact changes when transfer is toggled', async () => {
    const wrapper = await render(draft(), false);
    api.get.mockResolvedValue({ data: { ...options, receiving_members: options.members, receiving_liaison_suggestions: options.members } });
    await button(wrapper, 'Next').trigger('click');
    const selector = wrapper.findAllComponents(SearchSelect).find(s => s.props('placeholder').includes('panels'));
    selector.vm.$emit('update:modelValue', options.receiving_groups[0]);
    await flushPromises();
    expect(wrapper.find('dl').text()).toContain('Receiving CDWG');
    expect(wrapper.find('dl').text()).toContain('50049');
    expect(wrapper.find('dl input').exists()).toBe(false);
    expect(wrapper.findComponent(ContactListSelector).props('modelValue')).toHaveLength(1);
    await wrapper.find('table button').trigger('click');
    await wrapper.find('select').setValue('no');
    await wrapper.find('select').setValue('yes');
    expect(wrapper.findComponent(ContactListSelector).props('modelValue')).toHaveLength(0);
    expect(wrapper.find('dl').text()).toContain('Receiving CDWG');
    wrapper.unmount();
  });

  it('normalizes old single-contact drafts and does not replace selections with defaults', async () => {
    const legacy = draft();
    delete legacy.answers.liaisons;
    legacy.answers.liaison = { name: 'Legacy', email: 'old@example.org' };
    const wrapper = await render(legacy);
    expect(wrapper.find('table').text()).toContain('old@example.org');
    await wrapper.find('table button').trigger('click');
    api.put.mockImplementation((url, payload) => Promise.resolve({ data: { data: { ...draft(), answers: payload.answers, version: 2 } } }));
    await button(wrapper, 'Save draft').trigger('click');
    await flushPromises();
    expect(api.put.mock.calls[0][1].answers.liaisons).toEqual([]);
    expect(api.put.mock.calls[0][1].answers).not.toHaveProperty('liaison');
    expect(wrapper.find('table').exists()).toBe(false);
    wrapper.unmount();
  });

  it('never exposes start or edit controls to a read-only user', async () => {
    api.get.mockResolvedValue({ data: { data: null, can_start: false } });
    const wrapper = mount(InactivationRequest, { props: { group: { uuid: 'group', is_gcep: true } } });
    await flushPromises();
    expect(button(wrapper, 'Start Inactivation Request')).toBeUndefined();
    expect(api.post).not.toHaveBeenCalled();
    wrapper.unmount();
    const readonly = await render({ ...draft(), can_edit: false });
    expect(readonly.findComponent(SearchSelect).exists()).toBe(false);
    expect(button(readonly, 'Save draft')).toBeUndefined();
    expect(readonly.find('fieldset').attributes()).toHaveProperty('disabled');
    readonly.unmount();
  });

  it('confirms deletion, permits cancellation, and returns to Start after success', async () => {
    const wrapper = await render();
    await button(wrapper, 'Delete Draft').trigger('click');
    expect(wrapper.findComponent(ModalDialog).props('modelValue')).toBe(true);
    expect(wrapper.findComponent(ModalDialog).text()).toContain('permanently deleted');
    expect(api.delete).not.toHaveBeenCalled();
    await button(wrapper, 'Cancel').trigger('click');
    expect(wrapper.findComponent(ModalDialog).props('modelValue')).toBe(false);
    expect(api.delete).not.toHaveBeenCalled();
    await button(wrapper, 'Delete Draft').trigger('click');
    api.delete.mockResolvedValue({ data: { can_start: true } });
    await button(wrapper, 'Permanently Delete Draft').trigger('click');
    await flushPromises();
    expect(api.delete).toHaveBeenCalledWith('/api/groups/group/inactivation-request', { data: { uuid: 'request', version: 1 } });
    expect(button(wrapper, 'Start Inactivation Request')).toBeDefined();
    expect(button(wrapper, 'Delete Draft')).toBeUndefined();
    expect(wrapper.findComponent(ModalDialog).props('modelValue')).toBe(false);
    expect(document.body.style.overflow).not.toBe('hidden');
    api.post.mockResolvedValue({ data: { data: { ...draft(), uuid: 'replacement' } } });
    await button(wrapper, 'Start Inactivation Request').trigger('click');
    await flushPromises();
    expect(button(wrapper, 'Save draft')).toBeDefined();
    wrapper.unmount();
  });

  it.each(['submitted', 'revision_requested', 'pending_oc_review', 'approved'])('hides deletion for %s requests', async status => {
    const wrapper = await render({ ...draft(), status, can_edit: false, can_delete: false });
    expect(button(wrapper, 'Delete Draft')).toBeUndefined();
    wrapper.unmount();
  });

  it('hides deletion from unauthorized users and retains a draft after deletion failure', async () => {
    const readonly = await render({ ...draft(), can_edit: false, can_delete: false });
    expect(button(readonly, 'Delete Draft')).toBeUndefined();
    readonly.unmount();
    const wrapper = await render();
    await button(wrapper, 'Delete Draft').trigger('click');
    api.delete.mockRejectedValue({ response: { data: { message: 'This request changed. Reload it before deleting.' } } });
    await button(wrapper, 'Permanently Delete Draft').trigger('click');
    await flushPromises();
    expect(wrapper.findComponent(ModalDialog).text()).toContain('Reload it before deleting');
    expect(button(wrapper, 'Save draft')).toBeDefined();
    expect(button(wrapper, 'Start Inactivation Request')).toBeUndefined();
    await button(wrapper, 'Cancel').trigger('click');
    wrapper.unmount();
  });
});

describe('Contact list selector', () => {
  it('searches names/emails, prevents duplicate members, and keeps reference data', async () => {
    const wrapper = mount(ContactListSelector, { props: { modelValue: [], members: options.members } });
    const selector = wrapper.findComponent(SearchSelect);
    expect(selector.props('searchFunction')('liaison@example')).toEqual(options.members);
    expect(selector.props('searchFunction')('Test')).toEqual(options.members);
    selector.vm.setSelection(options.members[0]);
    await flushPromises();
    const selected = wrapper.emitted('update:modelValue')[0][0];
    expect(selected[0]).toMatchObject({ member_id: 7, person_id: 17, role: 'Grant Liaison' });
    await wrapper.setProps({ modelValue: selected });
    expect(wrapper.findComponent(SearchSelect).props('searchFunction')('Test')).toEqual([]);
    wrapper.findComponent(SearchSelect).vm.setSelection(options.members[0]);
    await flushPromises();
    expect(wrapper.emitted('update:modelValue')).toHaveLength(1);
    await wrapper.find('table button').trigger('click');
    expect(wrapper.emitted('update:modelValue').at(-1)[0]).toEqual([]);
    wrapper.unmount();
  });

  it('adds compact manual contacts and prevents changes while disabled', async () => {
    const wrapper = mount(ContactListSelector, { props: { modelValue: [], members: [], allowManual: true } });
    await button(wrapper, 'Add manual contact').trigger('click');
    await wrapper.find('[aria-label="Manual contact name"]').setValue('Manual Liaison');
    await wrapper.find('[aria-label="Manual contact email"]').setValue('manual@example.org');
    await button(wrapper, 'Add contact').trigger('click');
    const selected = wrapper.emitted('update:modelValue')[0][0];
    expect(selected[0]).toMatchObject({ member_id: null, person_id: null, name: 'Manual Liaison', email: 'manual@example.org' });
    await wrapper.setProps({ modelValue: selected, disabled: true });
    expect(wrapper.find('table').text()).toContain('Manual Liaison');
    expect(wrapper.find('button').exists()).toBe(false);
    expect(wrapper.findComponent(SearchSelect).exists()).toBe(false);
    wrapper.unmount();
  });
});
