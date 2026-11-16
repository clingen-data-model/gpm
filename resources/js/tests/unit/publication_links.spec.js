import { mount, flushPromises } from '@vue/test-utils'
import { reactive } from 'vue'
import { beforeEach, expect, it, vi } from 'vitest'
import PublicationsForm from '@/components/annual_update/PublicationsForm.vue'
import SearchSelect from '@/components/forms/SearchSelect.vue'
import GroupPublications from '@/views/groups/GroupPublications.vue'
import { api } from '@/http'

const state = vi.hoisted(() => ({ route: null, edit: true }))
vi.mock('vue-router', () => ({ useRoute: () => state.route }))
vi.mock('@/auth_utils', () => ({ hasRole: () => state.edit }))
vi.mock('@/http', () => ({ api: { get: vi.fn(), put: vi.fn() } }))
const group = { uuid: 'group-a' }
const a = { id: 1, group, source: 'pmcid', identifier: 'PMC1', published_at: '2024-01-01', meta: { title: 'Preprint title' } }
const b = { id: 2, group, source: 'pmcid', identifier: 'PMC2', meta: { title: 'Published title', pmcid: { id: 'PMC2' } } }
const c = { id: 3, group, source: 'pmid', identifier: '3', meta: { title: 'Replacement title' } }
const linked = () => [
  { ...a, published_publication_id: 2, published_publication: b },
  { ...b, preprint: a },
]
const mountPage = (annual = false) => mount(annual ? PublicationsForm : GroupPublications, {
  props: annual ? { group, modelValue: { id: 1, data: {} }, version: { for_year: 2025 } } : { group },
  global: {
    mocks: { formatDate: value => value, uid: 1 },
    stubs: { DateInput: true, RadioButton: true, InputErrors: true, ModalDialog: { props: ['modelValue'], template: '<div v-if="modelValue" class="dialog"><slot /></div>' } },
  },
})
const button = (wrapper, text) => wrapper.findAll('button').find(b => b.text() === text)
beforeEach(() => {
  vi.resetAllMocks()
  state.route = reactive({ query: {} })
  state.edit = true
  api.get.mockResolvedValue({ data: linked() })
  api.put.mockResolvedValue({})
})

it('shows both directions and opens related details in the existing dialog', async () => {
  state.route.query.publication = '1'
  const wrapper = mountPage()
  await flushPromises()
  let relation = wrapper.find('[data-testid="publication-relationship"]')
  expect(relation.text()).toContain('Published version:')
  expect(relation.text()).toContain('Published title')
  expect(relation.text()).toContain('PMCID: PMC2')
  expect(relation.find('a').attributes('href')).toBe('/groups/group-a?tab=publications&publication=2')
  await relation.find('a').trigger('click')
  relation = wrapper.find('[data-testid="publication-relationship"]')
  expect(relation.text()).toContain('Linked preprint:')
  expect(relation.text()).toContain('Preprint title')
  expect(relation.text()).toContain('PMCID: PMC1')
  expect(relation.text()).toContain('2024-01-01')
  expect(wrapper.findAll('tbody tr')).toHaveLength(1)
  wrapper.unmount()
})

it('opens details from a report or cross-group publication link for read-only users', async () => {
  state.edit = false
  state.route.query.publication = '2'
  const wrapper = mountPage()
  await flushPromises()
  expect(wrapper.find('[data-testid="publication-relationship"]').text()).toContain('Linked preprint:')
  expect(button(wrapper, 'Save link')).toBeUndefined()
  expect(button(wrapper, 'Remove link')).toBeUndefined()
  wrapper.unmount()
})

it('hides linking controls on either linked side and restores them after removing the link', async () => {
  for (const id of ['1', '2']) {
    state.route.query.publication = id
    api.get.mockResolvedValueOnce({ data: linked() })
    const w = mountPage()
    await flushPromises()
    expect(w.find('select[aria-label="Relationship direction"]').exists()).toBe(false)
    expect(w.find('input[aria-label="Find existing publication"]').exists()).toBe(false)
    expect(w.findComponent(SearchSelect).exists()).toBe(false)
    expect(button(w, 'Search')).toBeUndefined()
    expect(button(w, 'Save link')).toBeUndefined()
    api.get.mockResolvedValueOnce({ data: [a, b] })
    await button(w, 'Remove link').trigger('click')
    await flushPromises()
    expect(api.put).toHaveBeenLastCalledWith('/api/groups/group-a/publications/1/link', { published_publication_id: null, expected_published_publication_id: 2 })
    expect(w.findComponent(SearchSelect).exists()).toBe(true)
    expect(w.find('select[aria-label="Relationship direction"]').exists()).toBe(true)
    expect(w.findAll('tbody tr')).toHaveLength(2)
    expect(w.find('[data-testid="publication-relationship"]').exists()).toBe(false)
    w.unmount()
  }
})

it('links a preprint to its published version using the wrapped selector', async () => {
  api.get.mockResolvedValueOnce({ data: [a, b] })
  const w = mountPage()
  await flushPromises()
  await w.find('button[title="View details"]').trigger('click')
  api.get.mockResolvedValueOnce({ data: [b] })
  await button(w, 'Search').trigger('click')
  await flushPromises()
  await w.find('.filtered-option').trigger('mousedown')
  expect(w.find('.selection').text()).toContain('Published title')
  api.get.mockResolvedValueOnce({ data: linked() })
  await button(w, 'Save link').trigger('click')
  await flushPromises()
  expect(api.put).toHaveBeenLastCalledWith('/api/groups/group-a/publications/1/link', { published_publication_id: 2, expected_published_publication_id: null })
  expect(w.findAll('tbody tr')).toHaveLength(1)
  expect(w.findComponent(SearchSelect).exists()).toBe(false)
  w.unmount()
})

it('uses group-scoped search and displays backend link validation errors', async () => {
  api.get.mockResolvedValueOnce({ data: [a, b] })
  const w = mountPage()
  await flushPromises()
  await w.find('button[title="View details"]').trigger('click')
  await w.find('input[aria-label="Find existing publication"]').setValue('Published')
  api.get.mockResolvedValueOnce({ data: [b] })
  await button(w, 'Search').trigger('click')
  await flushPromises()
  expect(api.get).toHaveBeenLastCalledWith('/api/groups/group-a/publications/search', { params: { query: 'Published', publication_id: 1 } })
  await w.find('.filtered-option').trigger('mousedown')
  api.put.mockRejectedValueOnce({ response: { data: { message: 'Choose a publication from the same group.' } } })
  await button(w, 'Save link').trigger('click')
  await flushPromises()
  expect(w.text()).toContain('Choose a publication from the same group.')
  w.unmount()
})

it('loads ten recent candidates on dropdown focus and searches beyond them', async () => {
  api.get.mockResolvedValueOnce({ data: [a, b] })
  const w = mountPage()
  await flushPromises()
  await w.find('button[title="View details"]').trigger('click')
  const recent = Array.from({ length: 10 }, (_, i) => ({ id: i + 10, identifier: `PMC${i}`, meta: { title: `Recent ${i}` } }))
  api.get.mockResolvedValueOnce({ data: recent })
  await w.findComponent(SearchSelect).find('input').trigger('focus')
  await w.findComponent(SearchSelect).trigger('focusin')
  await flushPromises()
  expect(api.get).toHaveBeenLastCalledWith('/api/groups/group-a/publications/search', { params: { query: '', publication_id: 1 } })
  expect(w.findAll('.filtered-option')).toHaveLength(10)
  expect(w.find('.filtered-option').text()).toContain('Recent 0')
  expect(w.find('.filtered-option').text()).toContain('PMC0')
  api.get.mockResolvedValueOnce({ data: [c] })
  await w.find('input[aria-label="Find existing publication"]').setValue('replacement')
  await flushPromises()
  expect(w.findComponent(SearchSelect).text()).toContain('Replacement title')
  expect(w.findComponent(SearchSelect).text()).not.toContain('Recent 0')
  w.unmount()
})

it.each([false, true])('links and unlinks from the published article in either page (annual=%s)', async annual => {
  api.get.mockResolvedValueOnce({ data: [a, b] })
  const w = mountPage(annual)
  await flushPromises()
  await w.findAll('button[title="View details"]')[1].trigger('click')
  await w.find('select[aria-label="Relationship direction"]').setValue('preprint')
  api.get.mockResolvedValueOnce({ data: [a] })
  await w.findComponent(SearchSelect).find('input').trigger('focus')
  await w.findComponent(SearchSelect).trigger('focusin')
  await flushPromises()
  await w.find('.filtered-option').trigger('mousedown')
  api.get.mockResolvedValueOnce({ data: linked() })
  await button(w, 'Save link').trigger('click')
  await flushPromises()
  expect(api.put).toHaveBeenLastCalledWith('/api/groups/group-a/publications/1/link', { published_publication_id: 2, expected_published_publication_id: null })
  expect(w.findAll('tbody tr')).toHaveLength(1)
  expect(w.find('[data-testid="publication-relationship"]').text()).toContain('Linked preprint:')
  api.get.mockResolvedValueOnce({ data: [a, b] })
  await button(w, 'Remove link').trigger('click')
  await flushPromises()
  expect(api.put).toHaveBeenLastCalledWith('/api/groups/group-a/publications/1/link', { published_publication_id: null, expected_published_publication_id: 2 })
  expect(w.findAll('tbody tr')).toHaveLength(2)
  expect(w.find('[data-testid="publication-relationship"]').exists()).toBe(false)
  w.unmount()
})

it('keeps the full long title and identifier in options and the selected label', async () => {
  const long = { ...b, meta: { title: 'A very long publication title '.repeat(30) }, identifier: '10.123/' + 'x'.repeat(150) }
  api.get.mockResolvedValueOnce({ data: [a] })
  const w = mountPage()
  await flushPromises()
  await w.find('button[title="View details"]').trigger('click')
  api.get.mockResolvedValueOnce({ data: [long] })
  await button(w, 'Search').trigger('click')
  await flushPromises()
  expect(w.findComponent(SearchSelect).props('optionsHeight')).toBe(240)
  expect(w.find('.filtered-option').text()).toContain(long.meta.title.trim())
  expect(w.find('.filtered-option').text()).toContain(long.identifier)
  await w.find('.filtered-option').trigger('mousedown')
  expect(w.find('.selection').text()).toContain(long.meta.title.trim())
  expect(w.find('.selection').text()).toContain(long.identifier)
  expect(button(w, 'Save link').element.disabled).toBe(false)
  w.unmount()
})

it('searches remotely from the reused selector and clears back to recent candidates', async () => {
  api.get.mockResolvedValueOnce({ data: [a] })
  const w = mountPage()
  await flushPromises()
  await w.find('button[title="View details"]').trigger('click')
  api.get.mockResolvedValue({ data: [b] })
  await w.findComponent(SearchSelect).find('input').setValue('PuBlIsHeD')
  await new Promise(resolve => setTimeout(resolve, 10))
  await flushPromises()
  expect(api.get).toHaveBeenLastCalledWith('/api/groups/group-a/publications/search', { params: { query: 'PuBlIsHeD', publication_id: 1 } })
  expect(w.find('.filtered-option').text()).toContain('Published title')
  await w.findComponent(SearchSelect).find('input').setValue('')
  await flushPromises()
  expect(api.get).toHaveBeenLastCalledWith('/api/groups/group-a/publications/search', { params: { query: '', publication_id: 1 } })
  w.unmount()
})
