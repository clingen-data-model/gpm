import { mount, flushPromises } from '@vue/test-utils'
import { vi, describe, it, expect, beforeEach } from 'vitest'
import FundingAwardsTab from '@/components/expert_panels/FundingAwardsTab.vue'
import FundingSourceList from '@/views/FundingSourceList.vue'
import api from '@/http/api'

const auth = vi.hoisted(() => ({ admin: false, manage: true }))
const countries = [{ id: 1, name: 'United States' }]
vi.mock('@/http/api', () => ({ default: { get: vi.fn(), post: vi.fn(), put: vi.fn() } }))
vi.mock('@/auth_utils', () => ({ hasRole: () => auth.admin, hasPermission: () => auth.manage }))
vi.mock('vuex', () => ({ useStore: () => ({ getters: { 'countries/items': countries }, dispatch: vi.fn(), commit: vi.fn() }) }))

const sources = [
  { id: 1, uuid: 'source-a', name: 'NIH' },
  { id: 2, uuid: 'source-b', name: 'Other' },
]
const global = {
  directives: { remainingHeight: () => {} },
  stubs: {
    SubmissionWrapper: { template: '<div><slot /></div>' },
    ModalDialog: { template: '<div><slot /></div>' },
    DataTable: true, DropdownMenu: true, DropdownItem: true,
  },
}

beforeEach(() => {
  vi.clearAllMocks()
  auth.admin = false
  auth.manage = true
  api.get.mockResolvedValue({ data: [] })
  api.post.mockResolvedValue({ data: {} })
  api.put.mockResolvedValue({ data: {} })
})

describe('Funding awards', () => {
  it.each([false, true])('does not direct authorized managers to support (admin: %s)', async (admin) => {
    auth.admin = admin
    const wrapper = mount(FundingAwardsTab, { props: { expertPanel: { uuid: 'panel' } }, global })
    await flushPromises()
    expect(wrapper.find('[role="note"]').exists()).toBe(false)
    expect(wrapper.text()).toContain('Add Award')
    wrapper.unmount()
  })

  it('shows support to users who cannot manage awards', async () => {
    auth.manage = false
    const wrapper = mount(FundingAwardsTab, { props: { expertPanel: { uuid: 'panel' } }, global })
    await flushPromises()
    expect(wrapper.find('[role="note"]').text()).toContain('gpm_support@clinicalgenome.org')
    expect(wrapper.text()).not.toContain('Add Award')
    wrapper.unmount()
  })

  it('restores contacts, limits source options, prunes removals, and saves UUID references', async () => {
    const wrapper = mount(FundingAwardsTab, { props: { expertPanel: { uuid: 'panel' } }, global })
    await flushPromises()
    const vm = wrapper.vm
    vm.startEdit({ id: 7, funding_sources: sources, rep_contacts: [
      { name: 'Jane', publish: true, funding_sources: sources },
      { name: 'Legacy' },
    ] })
    await flushPromises()
    expect(vm.form.rep_contacts[1].funding_sources).toEqual([])
    const selects = wrapper.findAllComponents({ name: 'SearchSelect' })
    expect(selects[2].props('options')).toEqual(sources)
    expect(selects[2].props('keyOptionsBy')).toBe('uuid')
    vm.addRepContact()
    vm.addRepContact()
    expect(vm.form.rep_contacts).toHaveLength(3)
    vm.removeRepContact(2)
    vm.form.funding_sources = [sources[0]]
    await flushPromises()
    expect(vm.form.rep_contacts[0].funding_sources).toEqual([{ uuid: 'source-a', name: 'NIH' }])
    await vm.save()
    const payload = api.put.mock.calls[0][1]
    expect(payload.funding_source_ids).toEqual([1])
    expect(payload.rep_contacts[0].funding_sources).toEqual([{ uuid: 'source-a', name: 'NIH' }])
    expect(payload.rep_contacts[0].publish).toBe(true)
    wrapper.unmount()
  })
})

it('restores and clears source location using the shared country list', async () => {
  const wrapper = mount(FundingSourceList, { global })
  await flushPromises()
  const vm = wrapper.vm
  vm.startEdit({ id: 1, name: 'NIH', city: 'Bethesda', country_id: 1 })
  expect(vm.form.city).toBe('Bethesda')
  expect(vm.form.country_id).toBe(1)
  await vm.save()
  expect(api.post.mock.calls[0][1].get('city')).toBe('Bethesda')
  expect(api.post.mock.calls[0][1].get('country_id')).toBe('1')
  vm.form.city = ''
  vm.form.country_id = ''
  await vm.save()
  expect(api.post.mock.calls[1][1].get('country_id')).toBe('')
  wrapper.unmount()
})
