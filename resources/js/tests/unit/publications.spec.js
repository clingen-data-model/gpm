import { mount, flushPromises } from '@vue/test-utils'
import { beforeEach, vi, it, expect } from 'vitest'
import PublicationsForm from '@/components/annual_update/PublicationsForm.vue'
import PublicationManager from '@/components/groups/PublicationManager.vue'
import GroupPublications from '@/views/groups/GroupPublications.vue'
import { api } from '@/http'
const state = vi.hoisted(() => ({ edit: true }))
vi.mock('vue-router', () => ({ useRoute: () => ({ query: {} }) }))
vi.mock('@/auth_utils', () => ({ hasRole: () => state.edit }))
vi.mock('@/http', () => ({ api: { get: vi.fn(), post: vi.fn(), delete: vi.fn(), put: vi.fn() } }))
const group = { uuid: 'group' }
const old = { id: 1, source: 'pmid', identifier: '123', meta: { title: 'Old article' }, published_at: '2020-01-01' }
const current = { id: 2, source: 'pmcid', identifier: 'PMC2', meta: { title: 'Current article' }, published_at: '2025-02-01' }
const globals = {
  mocks: { formatDate: v => v, uid: 1 },
  stubs: {
    DateInput: true, RadioButton: true,
    ModalDialog: { props: ['modelValue'], template: '<div v-if="modelValue" class="dialog"><slot /></div>' },
    InputErrors: { props: ['errors'], template: '<div class="errors">{{ errors?.join(" ") }}</div>' },
  },
}
const form = (modelValue = { id: 1, data: {} }, extra = {}) => mount(PublicationsForm, {
  props: { modelValue, group, version: { for_year: 2025 }, ...extra }, global: globals,
})
const button = (w, label) => w.findAll('button').find(b => b.text() === label)
beforeEach(() => {
  vi.resetAllMocks()
  state.edit = true
  api.get.mockResolvedValue({ data: [old, current] })
})
it('both pages reuse the manager; drafts fetch current rows on reopening without inclusion controls', async () => {
  const tab = mount(GroupPublications, { props: { group }, global: globals })
  const w = form({ id: 1, data: { publications: [{ publication_id: 'obsolete', title: 'Saved draft row' }] } })
  await flushPromises()
  expect(tab.findComponent(PublicationManager).exists()).toBe(true)
  expect(w.findComponent(PublicationManager).exists()).toBe(true)
  expect(w.text()).toContain('Current article')
  expect(w.text()).not.toContain('Saved draft row')
  expect(w.findAll('tbody input[type="checkbox"]')).toHaveLength(0)
  expect(button(w, 'Include all')).toBeUndefined()
  expect(button(w, 'Refresh list')).toBeUndefined()
  expect(button(tab, 'Refresh list')).toBeUndefined()
  expect(w.findAll('[data-testid="publication-date"]')[0].classes()).toContain('text-gray-500')
  expect(w.findAll('[data-testid="publication-date"]')[1].classes()).toContain('text-blue-700')
  w.unmount()
  api.get.mockResolvedValue({ data: [current, { ...old, id: 3, meta: { title: 'Newly added' } }] })
  const reopened = form()
  await flushPromises()
  expect(reopened.text()).toContain('Newly added')
  reopened.unmount()
  tab.unmount()
})
it('defaults review to false, emits boolean changes, restores drafts and preserves notes and other answers', async () => {
  const w = form({ id: 1, data: { goals: 'Keep goals', publications_note: 'Existing note' } })
  const checkbox = w.find('input[name="publications_reviewed"]')
  expect(checkbox.element.checked).toBe(false)
  await checkbox.setValue(true)
  let saved = w.emitted('update:modelValue').at(-1)[0]
  expect(saved.data).toEqual({ goals: 'Keep goals', publications_note: 'Existing note', publications_reviewed: true })
  await w.setProps({ modelValue: saved })
  await w.find('textarea').setValue('Changed note')
  saved = w.emitted('update:modelValue').at(-1)[0]
  w.unmount()
  const reopened = form(saved)
  expect(reopened.find('input[name="publications_reviewed"]').element.checked).toBe(true)
  expect(reopened.find('textarea').element.value).toBe('Changed note')
  await reopened.find('input[name="publications_reviewed"]').setValue(false)
  expect(reopened.emitted('update:modelValue').at(-1)[0].data.publications_reviewed).toBe(false)
  await reopened.setProps({ errors: { publications_reviewed: ['Please confirm your review.'] } })
  expect(reopened.text()).toContain('Please confirm your review.')
  reopened.unmount()
})
it.each([{ publications: [] }, { publications: [{ publication_id: 'historic', source_type: 'pmid', source_id: '999', title: 'Historical snapshot', included: true, preprint: { publication_id: 'pre', title: 'Historic preprint', source_type: 'doi', source_id: '10/pre', published_at: '2024-01-01' } }] }])('completed views use snapshots exclusively and disable all management and confirmation edits', async ({ publications }) => {
  const w = form({ id: 1, completed_at: '2026-01-01', data: { publications, publications_reviewed: true, publications_note: 'Final note' } })
  await flushPromises()
  expect(api.get).not.toHaveBeenCalled()
  expect(button(w, 'Add publication')).toBeUndefined()
  expect(button(w, 'Remove')).toBeUndefined()
  expect(button(w, 'Refresh list')).toBeUndefined()
  expect(w.find('input[name="publications_reviewed"]').element.checked).toBe(true)
  expect(w.find('input[name="publications_reviewed"]').element.disabled).toBe(true)
  expect(w.find('textarea').element.disabled).toBe(true)
  if (publications.length) {
    expect(w.text()).toContain('Historical snapshot')
    expect(w.text()).toContain('Historic preprint')
    await button(w, 'Details').trigger('click')
    expect(button(w, 'Save link')).toBeUndefined()
    expect(button(w, 'Remove link')).toBeUndefined()
  }
  w.unmount()
})
it('adds and deletes through the same group APIs from the annual wrapper', async () => {
  const confirm = vi.spyOn(window, 'confirm').mockReturnValue(true)
  api.get.mockResolvedValueOnce({ data: [] })
  const w = form()
  await flushPromises()
  await button(w, 'Add publication').trigger('click')
  await w.find('.dialog input').setValue('PMC2')
  const preview = { ...current, meta: { title: 'Current article', authors: [] } }
  api.post.mockResolvedValueOnce({ data: preview }).mockResolvedValueOnce({ data: current })
  await button(w, 'Preview').trigger('click')
  await flushPromises()
  api.get.mockResolvedValueOnce({ data: [current] })
  await button(w, 'Save').trigger('click')
  await flushPromises()
  expect(api.post).toHaveBeenLastCalledWith('/api/groups/group/publications', preview)
  expect(w.find('tbody').text()).toContain('Current article')
  api.delete.mockResolvedValue({})
  api.get.mockResolvedValueOnce({ data: [] })
  await button(w, 'Remove').trigger('click')
  await flushPromises()
  expect(api.delete).toHaveBeenCalledWith('/api/groups/group/publications/2')
  expect(w.text()).toContain('No publications added yet.')
  expect(w.emitted('update:modelValue')).toBeUndefined()
  w.unmount()
  confirm.mockRestore()
})
it('retains group edit permissions and hides superseded preprints in annual drafts', async () => {
  state.edit = false
  api.get.mockResolvedValue({ data: [{ ...old, published_publication_id: 2 }, { ...current, preprint: old }] })
  const w = form()
  await flushPromises()
  expect(w.findAll('tbody tr')).toHaveLength(1)
  expect(w.text()).toContain('Linked preprint:')
  expect(w.text()).toContain('Old article')
  expect(button(w, 'Add publication')).toBeUndefined()
  expect(button(w, 'Remove')).toBeUndefined()
  w.unmount()
})
