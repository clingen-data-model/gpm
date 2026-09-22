import { mount, flushPromises } from '@vue/test-utils'
import { ref } from 'vue'
import { createStore } from 'vuex'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { useScopeOfWorkGeneRows } from '@/composables/scope_of_work_gene_rows'
import VcepGeneList from './VcepGeneList.vue'
import ScvcepGeneList from './ScvcepGeneList.vue'
import GeneCurationStatus from './GeneCurationStatus.vue'
import GcepGeneList from './GcepGeneList.vue'
import ScopeOfWorkGeneChangeLabel from '@/components/groups/ScopeOfWorkGeneChangeLabel.vue'

const api = vi.hoisted(() => ({ put: vi.fn(), post: vi.fn(), delete: vi.fn(), get: vi.fn() }))
vi.mock('@/http/api', () => ({ default: api }))
vi.mock('@/http', () => ({ default: api, api }))
vi.mock('@/auth_utils', () => ({ hasAnyPermission: () => true, hasRole: () => false }))
vi.mock('@/components/prosekit/RichTextEditor.vue', () => ({ default: { template: '<div />' } }))
vi.mock('@/components/forms/GeneSearchSelect.vue', () => ({ default: { template: '<div />' } }))
vi.mock('@/components/forms/DiseaseSearchSelect.vue', () => ({ default: { template: '<div />' } }))
vi.mock('@/components/forms/CuratedGeneSearchSelect.vue', () => ({ default: { template: '<div />' } }))

const gene = (id, tier = '1') => ({ id, gene_symbol: 'SAME', hgnc_id: 1, mondo_id: `MONDO:${id}`, disease_name: 'Disease', moi: 'AD', tier, plan: null, statuses: [], details: [] })
function fixture() {
  const live = [gene(1), gene(2), gene(3, '2')]
  const removed = { id: 4, gene_symbol: 'REMOVED', mondo_id: 'MONDO:4', disease_name: 'Old disease', moi: 'AR', tier: '1' }
  const rows = [
    { key: '1', operation: 'unchanged', before: gene(1), after: gene(1), field_changes: [] },
    { key: '2', operation: 'added', before: null, after: gene(2), field_changes: [] },
    { key: '3', operation: 'changed', before: gene(3), after: gene(3, '2'), field_changes: [{ field: 'tier', before: '1', after: '2' }] },
    { key: '4', operation: 'removed', before: removed, after: null, field_changes: [] },
  ]
  return { live, comparison: { source: 'live', unavailable_sections: [], rows: { genes: rows } } }
}

beforeEach(() => {
  vi.clearAllMocks()
  for (const method of Object.values(api)) method.mockResolvedValue({ data: [] })
})

async function render(Component, comparisonEnabled = true, liveOverride = null, discardContext = null) {
  const { live, comparison } = fixture()
  const group = { uuid: 'group', expert_panel: { genes: liveOverride ?? live } }
  const getGenes = vi.fn()
  const store = createStore({ modules: { groups: { namespaced: true,
    getters: { currentItemOrNew: () => group }, actions: { getGenes },
  } }, mutations: { pushSuccess: vi.fn(), pushError: vi.fn() } })
  const wrapper = mount(Component, {
    props: { scopeComparison: comparisonEnabled ? comparison : null,
      ...(Component === GeneCurationStatus ? { genes: group.expert_panel.genes, groupID: 'group' } : {}) },
    global: { plugins: [store], provide: { scopeOfWorkGeneDiscard: discardContext }, mocks: { hasRole: () => false, hasAnyPermission: () => true },
      stubs: { note: true, 'edit-icon-button': true, 'dropdown-menu': { template: '<div><slot name="label" /><slot /></div>' }, 'dropdown-item': true } },
  })
  await flushPromises()
  return { wrapper, comparison, getGenes }
}

describe('gene comparison display adapter', () => {
  it('preserves live references and keeps duplicate symbols distinct by ID', () => {
    const { live, comparison } = fixture()
    const rows = useScopeOfWorkGeneRows(() => live, () => comparison)
    expect(rows.rows.value.map(row => row.key)).toEqual(['1', '2', '3', '4'])
    expect(rows.rows.value[0].gene).toBe(live[0])
    expect(rows.rows.value[3].snapshotOnly).toBe(true)
    expect(rows.rows.value[3].gene).not.toBe(comparison.rows.genes[3].before)
    expect(rows.editableIds([1, '2', 4, 99])).toEqual([1, 2])
    expect(rows.canMutate(rows.rows.value[3].gene)).toBe(false)
    expect(live).toHaveLength(3)
    expect(live[0]).not.toHaveProperty('comparison')
  })

  it('uses after for absent submitted rows without allowing mutation, and falls back when unavailable', () => {
    const { comparison } = fixture()
    const state = ref(comparison)
    const rows = useScopeOfWorkGeneRows(() => [], () => state.value)
    expect(rows.rows.value[2].gene.tier).toBe('2')
    expect(rows.editableIds([1, 2, 3, 4])).toEqual([])
    state.value = { ...comparison, unavailable_sections: ['genes'] }
    expect(rows.rows.value).toEqual([])
    state.value = null
    expect(rows.rows.value).toEqual([])
  })

  it('blocks removed metadata even when the live collection has not refreshed yet', () => {
    const { comparison } = fixture()
    const live = [gene(4)]
    const state = ref(comparison)
    const rows = useScopeOfWorkGeneRows(() => live, () => state.value)
    expect(rows.rows.value).toHaveLength(4)
    expect(rows.canMutate(live[0])).toBe(false)
    expect(rows.editableIds([4])).toEqual([])
    state.value = { ...comparison, status: 'unavailable' }
    expect(rows.rows.value).toHaveLength(1)
    expect(rows.rows.value[0].gene).toBe(live[0])
    expect(rows.comparisonFor(live[0])).toBe(null)
  })
})

describe.each([
  ['VCEP', VcepGeneList, 'Current', 'Future'],
  ['SC-VCEP', ScvcepGeneList, 'Current', 'Future'],
  ['GCEP', GeneCurationStatus, 'Primary', 'Secondary'],
])('%s contextual cards', (name, Component, oldTier, newTier) => {
  it('offers revision discard on added, removed and changed cards only while editable', async () => {
    const revision = { uuid: 'revision', status: 'draft', changes: [
      { id: 20, rule_key: 'gene.add', after_value: { id: 2 }, can_discard: true },
      { id: 30, rule_key: 'gene.update_tier', field_name: 'tier', after_value: { id: 3 }, can_discard: true },
      { id: 40, rule_key: 'gene.remove', before_value: { id: 4 }, can_discard: true },
    ] }
    const context = { status: ref({ active_revision: revision }), busy: ref(false), discard: vi.fn() }
    const { wrapper } = await render(Component, true, null, context)
    expect(wrapper.get('[data-scope-gene-id="1"]').find('[aria-label^="Discard"]').exists()).toBe(false)
    for (const id of [2, 3, 4]) expect(wrapper.get(`[data-scope-gene-id="${id}"]`).find('[aria-label^="Discard"]').exists()).toBe(true)
    const removed = wrapper.get('[data-scope-gene-id="4"]')
    expect(removed.findAll('button')).toHaveLength(1)
    expect(removed.find('input, select, dropdown-item-stub').exists()).toBe(false)
    await removed.get('button').trigger('click')
    expect(context.discard).toHaveBeenCalledWith({ revision: context.status.value.active_revision, changeId: 40, geneLabel: undefined })
    context.busy.value = true
    await flushPromises()
    expect(wrapper.findAll('[aria-label^="Discard"]').every(button => button.element.disabled)).toBe(true)
    await removed.get('button').trigger('click')
    expect(context.discard).toHaveBeenCalledTimes(1)
    context.status.value.active_revision.status = 'submitted'
    await flushPromises()
    expect(wrapper.findAll('[aria-label^="Discard"]')).toHaveLength(0)
    context.status.value.active_revision.status = 'revisions_requested'
    await flushPromises()
    expect(wrapper.findAll('[aria-label^="Discard"]')).toHaveLength(3)
    wrapper.unmount()
  })
  it('shows the union in existing cards, badges and backend field changes', async () => {
    const { wrapper } = await render(Component)
    expect(wrapper.findAll('[data-scope-gene-id]')).toHaveLength(4)
    const unchanged = wrapper.get('[data-scope-gene-id="1"]')
    expect(unchanged.find('del').exists()).toBe(false)
    expect(unchanged.text()).not.toMatch(/Added|Removed/)
    expect(wrapper.get('[data-scope-gene-id="2"]').text()).toContain('Added')
    const changed = wrapper.get('[data-scope-gene-id="3"]')
    expect(changed.get('del').text()).toBe(oldTier)
    expect(changed.get('ins').text()).toBe(newTier)
    const removed = wrapper.get('[data-scope-gene-id="4"]')
    expect(removed.text()).toContain('REMOVED')
    expect(removed.text()).toContain('Removed')
    expect(removed.find('.line-through').exists()).toBe(true)
    expect(removed.find('input, select, button, dropdown-item-stub').exists()).toBe(false)
    expect(removed.text()).not.toMatch(/Not Curated|No Gene Curation/)
    wrapper.vm.search = 'MONDO:4'
    await flushPromises()
    expect(wrapper.findAll('[data-scope-gene-id]')).toHaveLength(1)
    wrapper.unmount()
  })

  it('blocks historical mutation methods and sanitizes selection before bulk requests', async () => {
    const { wrapper } = await render(Component)
    const historical = wrapper.vm.displayGenes.find(g => g.id === 4)
    wrapper.vm.toggleSelect(4)
    expect(wrapper.vm.selectedGenes).toEqual([])
    wrapper.vm.toggleSelectAll()
    expect(wrapper.vm.selectedGenes).toEqual([1, 2, 3])
    await wrapper.vm.updateTier(historical)
    wrapper.vm.confirmRemove(historical)
    if (Component === ScvcepGeneList) await wrapper.vm.removeGene()
    else await wrapper.vm.removeGenes()
    if (Component !== GeneCurationStatus) {
      wrapper.vm.startEdit(historical)
      expect(wrapper.vm.isFormVisible).toBe(false)
      if (Component === ScvcepGeneList) wrapper.vm.isEditingId = 4
      else wrapper.vm.isEditing = 4
      await wrapper.vm.saveForm()
    }
    if (Component === VcepGeneList) await wrapper.vm.applyGtUpdate(historical)
    expect(api.put).not.toHaveBeenCalled()
    expect(api.delete).not.toHaveBeenCalled()
    expect(api.post).not.toHaveBeenCalled()
    wrapper.vm.selectedGenes = [1, 4, 99]
    wrapper.vm.bulkTier = '2'
    await wrapper.vm.applyBulkTier()
    expect(api.put).toHaveBeenCalledWith('/api/groups/group/expert-panel/genes/update-tier', { ids: [1], tier: '2' })
    expect(wrapper.emitted('saved')).toHaveLength(1)
    wrapper.unmount()
  })

  it('retains ordinary live behavior without comparison and refreshes after individual tier save', async () => {
    const { wrapper } = await render(Component, false)
    expect(wrapper.findAll('[data-scope-gene-id]')).toHaveLength(3)
    expect(wrapper.text()).not.toContain('Removed')
    expect(wrapper.text()).not.toContain('Added')
    expect(wrapper.find('del').exists()).toBe(false)
    await wrapper.vm.updateTier(wrapper.vm.displayGenes[0])
    expect(wrapper.emitted('saved')).toHaveLength(1)
    await wrapper.setProps({ scopeComparison: { unavailable_sections: ['genes'], rows: { genes: fixture().comparison.rows.genes } } })
    expect(wrapper.findAll('[data-scope-gene-id]')).toHaveLength(3)
    expect(wrapper.find('del').exists()).toBe(false)
    wrapper.unmount()
  })
})

it('keeps GCEP expansion on the same scope-gene ID after sorting', async () => {
  const { wrapper, comparison } = await render(GeneCurationStatus, false)
  const genes = fixture().live
  genes[2].details = [{ disease_name: 'Disease', curation_status: 'Published' }]
  await wrapper.setProps({ genes })
  await wrapper.get('[data-scope-gene-id="3"] button[title="Toggle details"]').trigger('click')
  await wrapper.setProps({ scopeComparison: comparison })
  wrapper.vm.sortOrder = 'desc'
  wrapper.vm.pageSize = 2
  await flushPromises()
  expect(wrapper.vm.expanded).toEqual([3])
  wrapper.vm.pageSize = 20
  await flushPromises()
  expect(wrapper.get('[data-scope-gene-id="3"]').find('#gene-details-3').exists()).toBe(true)
  expect(wrapper.get('[data-scope-gene-id="4"]').find('[id^="gene-details-"]').exists()).toBe(false)
  wrapper.unmount()
})

it('escapes comparison text and uses semantic deletion/insertion', () => {
  const wrapper = mount(ScopeOfWorkGeneChangeLabel, { props: { tierLabel: String,
    comparison: { operation: 'changed', field_changes: [{ field: 'disease_name', before: '<script>old</script>', after: '<img src=x onerror=alert(1)>' }] },
  } })
  expect(wrapper.get('del').text()).toBe('<script>old</script>')
  expect(wrapper.get('ins').text()).toBe('<img src=x onerror=alert(1)>')
  expect(wrapper.find('script, img').exists()).toBe(false)
  wrapper.unmount()
})

it('SC-VCEP removal emits the existing saved refresh event', async () => {
  const { wrapper, getGenes } = await render(ScvcepGeneList)
  wrapper.vm.confirmRemove(wrapper.vm.displayGenes[0])
  await wrapper.vm.removeGene()
  expect(api.delete).toHaveBeenCalledWith('/api/groups/group/expert-panel/genes', { data: { ids: [1] } })
  expect(wrapper.emitted('saved')).toHaveLength(1)
  expect(getGenes).toHaveBeenCalledTimes(2)
  wrapper.unmount()
})

it('GCEP retains snapshot-only cards with no live genes and forwards saved refresh', async () => {
  const { wrapper, getGenes } = await render(GcepGeneList, true, [])
  const child = wrapper.getComponent(GeneCurationStatus)
  expect(child.findAll('[data-scope-gene-id]')).toHaveLength(4)
  expect(child.findAll('[data-scope-gene-id] input, [data-scope-gene-id] select')).toHaveLength(0)
  child.vm.$emit('saved')
  await flushPromises()
  expect(getGenes).toHaveBeenCalledTimes(2)
  expect(wrapper.emitted('saved')).toHaveLength(1)
  wrapper.unmount()
})
