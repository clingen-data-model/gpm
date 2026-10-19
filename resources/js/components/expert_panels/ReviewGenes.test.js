import { mount } from '@vue/test-utils'
import { ref } from 'vue'
import { createStore } from 'vuex'
import { describe, it, expect } from 'vitest'
import ReviewGenes from './ReviewGenes.vue'

describe('shared review gene comparison', () => {
  it('does not substitute live genes when the submitted snapshot is missing', () => {
    const wrapper = mount(ReviewGenes, { global: {
      plugins: [createStore({ getters: { 'groups/currentItemOrNew': () => ({ is_gcep: true, expert_panel: { genes: [{ id: 1, gene_symbol: 'LIVE' }] } }) } })],
      provide: { scopeOfWorkComparisonState: { comparison: ref({ mode: 'approved_baseline', unavailable_sections: ['genes'], rows: { genes: null } }) } },
      stubs: { GeneCurationStatus: true, VcepGeneList: true, ScvcepGeneList: true },
    } })
    expect(wrapper.text()).toContain('unavailable')
    expect(wrapper.findComponent({ name: 'GeneCurationStatus' }).exists()).toBe(false)
    expect(wrapper.text()).not.toContain('LIVE')
  })
  it.each([
    ['gcep', 'GeneCurationStatus'], ['vcep', 'VcepGeneList'], ['scvcep', 'ScvcepGeneList'],
  ])('passes the shared frozen round to %s rendering', (type, component) => {
    const comparison = ref({ mode: 'previous_review_round', rows: { genes: [] } })
    const group = { uuid: 'group', is_gcep: type === 'gcep', is_scvcep: type === 'scvcep',
      is_vcep_or_scvcep: type !== 'gcep', expert_panel: { genes: [] } }
    const wrapper = mount(ReviewGenes, { global: {
      plugins: [createStore({ getters: { 'groups/currentItemOrNew': () => group } })],
      provide: { scopeOfWorkComparisonState: { comparison } },
      stubs: { GeneCurationStatus: true, VcepGeneList: true, ScvcepGeneList: true },
    } })
    const list = wrapper.findComponent({ name: component })
    expect(list.props('scopeComparison')).toEqual(comparison.value)
    expect(list.props('readonly')).toBe(true)
    expect(list.props('editing')).toBe(false)
    wrapper.unmount()
  })
})
