import { mount, flushPromises } from '@vue/test-utils'
import { reactive } from 'vue'
import { beforeEach, expect, it, vi } from 'vitest'
import GroupPublications from '@/views/groups/GroupPublications.vue'
import { api } from '@/http'

const state = vi.hoisted(() => ({ route: null, edit: true }))
vi.mock('vue-router', () => ({ useRoute: () => state.route }))
vi.mock('@/auth_utils', () => ({ hasRole: () => state.edit }))
vi.mock('@/http', () => ({ api: { get: vi.fn(), put: vi.fn() } }))
const group = { uuid: 'group-a' }
const a = { id: 1, group, source: 'pmcid', identifier: 'PMC1', meta: { title: 'Preprint title' } }
const b = { id: 2, group, source: 'pmcid', identifier: 'PMC2', meta: { title: 'Published title', pmcid: { id: 'PMC2' } } }
const c = { id: 3, group: { uuid: 'group-b' }, source: 'pmid', identifier: '3', meta: { title: 'Replacement title' } }
const linked = () => [
  { ...a, published_publication_id: 2, published_publication: b },
  { ...b, preprint: a },
]
const mountPage = () => mount(GroupPublications, {
  props: { group },
  global: {
    mocks: { formatDate: value => value },
    stubs: { ModalDialog: { props: ['modelValue'], template: '<div v-if="modelValue" class="dialog"><slot /></div>' } },
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
  const wrapper = mountPage()
  await flushPromises()
  await wrapper.findAll('button[title="View details"]')[0].trigger('click')
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
  wrapper.unmount()
})

it('opens details from a report or cross-group publication link for read-only users', async () => {
  state.edit = false
  state.route.query.publication = '2'
  const wrapper = mountPage()
  await flushPromises()
  expect(wrapper.find('[data-testid="publication-relationship"]').text()).toContain('Linked preprint:')
  expect(button(wrapper, 'Save link')).toBeUndefined()
  wrapper.unmount()
})

it('refreshes relationships immediately after adding, changing and removing a link', async () => {
  api.get.mockResolvedValueOnce({ data: [a, b] })
  const wrapper = mountPage()
  await flushPromises()
  await wrapper.findAll('button[title="View details"]')[0].trigger('click')
  expect(wrapper.find('[data-testid="publication-relationship"]').exists()).toBe(false)

  for (const target of [b, c]) {
    await wrapper.find('input[aria-label="Find existing publication"]').setValue('title')
    api.get.mockResolvedValueOnce({ data: [target] })
    await button(wrapper, 'Search').trigger('click')
    await flushPromises()
    await wrapper.find('select').setValue(target.id)
    api.get.mockResolvedValueOnce({ data: [{ ...a, published_publication_id: target.id, published_publication: target }, b] })
    await button(wrapper, 'Save link').trigger('click')
    await flushPromises()
    expect(api.put).toHaveBeenLastCalledWith('/api/groups/group-a/publications/1/link', { published_publication_id: target.id })
    expect(wrapper.find('[data-testid="publication-relationship"]').text()).toContain(target.meta.title)
  }
  expect(wrapper.find('[data-testid="publication-relationship"] a').attributes('href')).toBe('/groups/group-b?tab=publications&publication=3')
  api.get.mockResolvedValueOnce({ data: [a, b] })
  await button(wrapper, 'Remove link').trigger('click')
  await flushPromises()
  expect(api.put).toHaveBeenLastCalledWith('/api/groups/group-a/publications/1/link', { published_publication_id: null })
  expect(wrapper.find('[data-testid="publication-relationship"]').exists()).toBe(false)
  expect(wrapper.find('tbody').text()).not.toContain('Replacement title')
  wrapper.unmount()
})
