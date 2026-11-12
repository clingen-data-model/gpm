import { mount, flushPromises } from '@vue/test-utils'
import { vi, it, expect } from 'vitest'
import PublicationsForm from '@/components/annual_update/PublicationsForm.vue'
import { api } from '@/http'
vi.mock('@/http', () => ({ api: { get: vi.fn() } }))

it('offers out-of-period publications, distinguishes dates, and saves inclusion', async () => {
  vi.useFakeTimers()
  api.get.mockResolvedValue({ data: [
    { publication_id: 'old', published_at: '2020-01-01', title: 'Old' },
    { publication_id: 'current', published_at: '2025-02-01', title: 'Current' },
  ] })
  const wrapper = mount(PublicationsForm, {
    props: { modelValue: { data: {} }, version: { for_year: 2025 }, groupUuid: 'group' },
    global: { mocks: { formatDate: value => value } },
  })
  await flushPromises()
  expect(api.get.mock.calls[0][1].params).not.toHaveProperty('start')
  expect(api.get.mock.calls[0][1].params).not.toHaveProperty('end')
  const rows = wrapper.findAll('tbody tr')
  expect(rows[0].find('td:last-child').classes()).toContain('text-blue-700')
  expect(rows[1].find('td:last-child').classes()).toContain('text-gray-500')
  const checkbox = rows[1].find('input')
  expect(checkbox.element.disabled).toBe(false)
  await checkbox.setValue(true)
  await vi.advanceTimersByTimeAsync(650)
  const saved = wrapper.emitted('update:modelValue').at(-1)[0]
  expect(saved.data.publications.find(p => p.publication_id === 'old').included).toBe(true)
  wrapper.unmount()
  vi.useRealTimers()
})
