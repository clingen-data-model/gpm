import { mount, flushPromises } from '@vue/test-utils'
import { vi, it, expect } from 'vitest'
import Reports from '@/views/Reports.vue'
import { api } from '@/http'
vi.mock('@/http', () => ({ api: { get: vi.fn() } }))

it('uses the unique metric for the quality card and preserves the record total elsewhere', async () => {
  api.get.mockResolvedValue({ data: [
    { Metric: 'Number of Publications', Value: 158 },
    { Metric: 'Unique publications', Value: 153 },
  ] })
  const wrapper = mount(Reports, { global: {
    directives: { remainingHeight: () => {} },
    stubs: { DownloadLink: { template: '<span><slot /></span>' } },
  } })
  await flushPromises()
  const card = wrapper.findAll('article').find(a => a.text().includes('Representation & data quality'))
  const rows = card.findAll('.metric-row').filter(row => /publications/i.test(row.text()))
  expect(rows).toHaveLength(1)
  expect(rows[0].text()).toContain('153')
  expect(rows[0].text()).not.toContain('158')
  expect(wrapper.text()).toContain('158')
  wrapper.unmount()
})
