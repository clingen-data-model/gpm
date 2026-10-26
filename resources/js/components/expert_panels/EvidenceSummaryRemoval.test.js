import { mount, flushPromises } from '@vue/test-utils'
import { createStore } from 'vuex'
import { describe, it, expect, vi, beforeEach } from 'vitest'
import EvidenceSummaryList from './EvidenceSummaryList.vue'
import EvidenceSummaryForm from './EvidenceSummaryForm.vue'
import EvidenceSummaryScopeStatus from './EvidenceSummaryScopeStatus.vue'
import EvidenceSummary from './EvidenceSummary.vue'
import GeneEvidenceRemovalWarning from './GeneEvidenceRemovalWarning.vue'

const api = vi.hoisted(() => Object.assign(vi.fn(), { get: vi.fn() }))
vi.mock('@/http/api', () => ({ default: api }))
const gene = { id: 10, gene_symbol: 'NTRK3', mondo_id: 'MONDO:1', disease_name: 'Disease A', deleted_at: '2026-10-23' }
const evidence = () => ({ id: 1, gene_id: 10, gene: { ...gene }, variant: 'Variant A', summary: 'Preserved evidence', gene_scope_status: 'pending_removal' })
const panel = type => ({ id: 1, uuid: 'group', is_vcep: type === 'vcep', is_scvcep: type === 'scvcep', expert_panel: { genes: [], evidence_summaries: [] } })
const stubs = { 'input-row': { template: '<div><slot /></div>' }, 'button-row': true, 'dropdown-menu': true, 'dropdown-item': true, 'modal-dialog': true }
beforeEach(() => { vi.clearAllMocks(); api.get.mockResolvedValue({ data: [{ id: 20, gene_symbol: 'ACTIVE' }] }); api.mockResolvedValue({ data: { data: evidence() } }) })

describe.each(['vcep', 'scvcep'])('%s evidence for removed scope genes', type => {
  it('keeps the removed summary visible and refreshes its warning after partial discard or approval', async () => {
    const group = panel(type)
    let result = evidence()
    const load = vi.fn(() => [result])
    const store = createStore({ getters: { 'groups/currentItemOrNew': () => group }, actions: { 'groups/getEvidenceSummaries': load } })
    const wrapper = mount(EvidenceSummaryList, { props: { scopeOfWorkStatus: { active_revision: { id: 2 } } },
      global: { plugins: [store], stubs, mocks: { hasAnyPermission: () => true } } })
    await flushPromises()
    expect(wrapper.text()).toContain('Preserved evidence')
    expect(wrapper.text()).toContain('NTRK3')
    expect(wrapper.text()).toContain('Disease A')
    expect(wrapper.text()).toContain('Pending removal from Scope of Work')
    expect(wrapper.find('.border-red-500').exists()).toBe(true)
    expect(wrapper.findAll('header [role="status"]')).toHaveLength(1)
    expect(wrapper.find('header [role="status"]').element.tagName).toBe('SPAN')
    expect(wrapper.find('.bg-amber-50').exists()).toBe(false)
    result = { ...evidence(), gene: { ...gene, deleted_at: null }, gene_scope_status: null }
    await wrapper.setProps({ scopeOfWorkStatus: { active_revision: null } })
    await flushPromises()
    expect(wrapper.find('[role="status"]').exists()).toBe(false)
    expect(wrapper.find('.border-red-500').exists()).toBe(false)
    expect(wrapper.text()).toContain('Preserved evidence')
    result = { ...evidence(), gene_scope_status: 'removed' }
    await wrapper.setProps({ scopeOfWorkStatus: { approved_version: { id: 2 } } })
    await flushPromises()
    expect(wrapper.text()).toContain('Removed from Scope of Work')
    expect(wrapper.findAll('[role="status"]')).toHaveLength(1)
    expect(wrapper.find('.border-red-500').exists()).toBe(true)
    wrapper.unmount()
  })
  it('shows the removed association separately and omits gene ID when editing content without replacement', async () => {
    const summary = evidence()
    const wrapper = mount(EvidenceSummaryForm, { props: { group: panel(type), summary },
      global: { plugins: [createStore({ mutations: { pushSuccess: vi.fn() } })], stubs } })
    await flushPromises()
    expect(wrapper.get('select').element.selectedIndex).toBe(0)
    expect(wrapper.get('select').text()).not.toContain('NTRK3')
    expect(wrapper.get('select').text()).toContain('ACTIVE')
    expect(wrapper.get('[data-testid="current-association"]').text()).toContain('NTRK3 — MONDO:1 Disease A')
    expect(wrapper.text()).toContain('Pending removal from Scope of Work')
    await wrapper.get('textarea').setValue('Edited evidence')
    wrapper.vm.summaryClone.variant = 'Edited variant'
    wrapper.vm.summaryClone.assertion_id = 'Edited assertion'
    wrapper.vm.summaryClone.vci_url = 'https://example.org/evidence'
    await wrapper.vm.save()
    expect(api).toHaveBeenCalledWith(expect.objectContaining({ method: 'put', data: expect.objectContaining({ summary: 'Edited evidence', variant: 'Edited variant', assertion_id: 'Edited assertion', vci_url: 'https://example.org/evidence' }) }))
    expect(api.mock.calls[0][0].data).not.toHaveProperty('gene_id')
    expect(summary.gene_id).toBe(10)
    expect(summary.summary).toBe('Preserved evidence')
    wrapper.unmount()
  })
  it('sends an explicit active replacement, and clearing the optional selector preserves the original', async () => {
    const wrapper = mount(EvidenceSummaryForm, { props: { group: panel(type), summary: evidence() },
      global: { plugins: [createStore({ mutations: { pushSuccess: vi.fn() } })], stubs } })
    await flushPromises()
    await wrapper.get('select').setValue('20')
    await wrapper.vm.save()
    expect(api.mock.calls[0][0].data.gene_id).toBe(20)
    await wrapper.get('select').setValue(wrapper.find('option').element.value)
    await wrapper.vm.save()
    expect(api.mock.calls[1][0].data).not.toHaveProperty('gene_id')
    await wrapper.setProps({ summary: { ...evidence(), id: 2, gene_id: 20, gene: { id: 20, gene_symbol: 'ACTIVE' } } })
    expect(wrapper.get('select').element.value).toBe('20')
    expect(wrapper.find('[data-testid="current-association"]').exists()).toBe(false)
    wrapper.unmount()
  })
  it('offers only active genes for new summaries, even if a removed row is supplied', async () => {
    api.get.mockResolvedValue({ data: [gene, { id: 20, gene_symbol: 'ACTIVE' }] })
    const wrapper = mount(EvidenceSummaryForm, { props: { group: panel(type), summary: { gene_id: null } }, global: { stubs } })
    await flushPromises()
    expect(wrapper.findAll('option').map(option => option.element.value)).toEqual(['Select...', '20'])
    expect(wrapper.text()).not.toContain('NTRK3')
    wrapper.unmount()
  })
})
it('does not introduce removal status into GCEP', () => {
  const wrapper = mount(EvidenceSummaryScopeStatus, { props: { group: panel('gcep'), summary: evidence() } })
  expect(wrapper.text()).toBe('')
  const card = mount(EvidenceSummary, { props: { group: panel('gcep'), summary: evidence() },
    global: { stubs, mocks: { hasAnyPermission: () => false } } })
  expect(card.find('.border-red-500').exists()).toBe(false)
  expect(card.find('[role="status"]').exists()).toBe(false)
  card.unmount()
})
it.each([0, 1, 2])('shows an informational warning for %i evidence summaries', count => {
  const wrapper = mount(GeneEvidenceRemovalWarning, { props: { gene: { ...gene, evidence_summaries_count: count } } })
  if (!count) expect(wrapper.text()).toBe('')
  else {
    expect(wrapper.text()).toContain(`This gene-disease has ${count} ${count === 1 ? 'Evidence Summary.' : 'Evidence Summaries.'}`)
    expect(wrapper.text()).toContain('Removing it from the Scope of Work will not delete those summaries.')
    expect(wrapper.text()).toContain('They will remain associated with this gene-disease.')
  }
})
