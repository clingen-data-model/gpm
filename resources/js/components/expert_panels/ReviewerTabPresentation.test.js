import { mount } from '@vue/test-utils'
import { ref } from 'vue'
import { createStore } from 'vuex'
import { describe, it, expect } from 'vitest'
import DefinitionReview from './DefinitionReview.vue'
import SustainedCurationReview from './SustainedCurationReview.vue'
import ReviewSection from './ReviewSection.vue'
import ReviewGenes from './ReviewGenes.vue'
function render(component, type, comparison = null) {
  const group = { id: 1, uuid: 'panel', type: { display_name: type }, is_vcep_or_scvcep: true, is_scvcep: type === 'scvcep',
    description: 'Current description', members: [], coreApprovalMembers: [], biocuratorTrainers: [], trainedBiocurators: [],
    expert_panel: { genes: [], has_approved_pilot: true, scope_description: 'Current scope', membership_description: 'Current expertise' } }
  return mount(component, { global: { plugins: [createStore({ getters: { 'groups/currentItemOrNew': () => group } })],
    provide: { commentManager: ref({ comments: [] }), scopeOfWorkComparisonState: { comparison: ref(comparison) },
      initialApplicationReview: ref({ step: component === DefinitionReview ? 1 : 4 }) },
    stubs: { ReviewMembership: true, EvidenceSummaryList: true, VcepGeneList: true, ScvcepGeneList: true,
      'object-dictionary': true, 'dictionary-row': { template: '<div><slot /></div>' }, 'markdown-block': true,
      'icon-cheveron-down': true, 'icon-cheveron-right': true, badge: true },
    mocks: { titleCase: v => v },
  } })
}
describe('normal reviewer tab presentation', () => {
  it.each(['vcep', 'scvcep'])('keeps existing Group Definition sections and comments for initial %s', type => {
    const wrapper = render(DefinitionReview, type)
    expect(wrapper.findAllComponents(ReviewSection).map(c => c.props('name'))).toEqual(['basic-info', 'membership', 'website-description', 'scope', 'attestations'])
    expect(wrapper.findComponent({ name: 'ReviewMembership' }).exists()).toBe(true)
    expect(wrapper.text()).toContain('Current scope')
    expect(wrapper.text()).toContain('Add comment')
    expect(wrapper.text()).not.toContain('Frozen submitted application')
    expect(wrapper.findComponent({ name: 'ReviewRoundDetail' }).exists()).toBe(false)
  })
  it.each(['vcep', 'scvcep'])('keeps Sustained Curation sections with no standalone gene summary for %s', type => {
    for (const comparison of [null, { mode: 'approved_baseline', rows: { genes: [] }, changes: [] }]) {
      const wrapper = render(SustainedCurationReview, type, comparison)
      expect(wrapper.findAllComponents(ReviewSection).map(c => c.props('name'))).toEqual(['discrepency-review', 'evidence-summaries', 'member-designation'])
      expect(wrapper.findComponent({ name: 'EvidenceSummaryList' }).exists()).toBe(true)
      expect(wrapper.findComponent(ReviewGenes).exists()).toBe(false)
      expect(wrapper.findComponent({ name: 'ReviewRoundDetail' }).exists()).toBe(false)
      expect(wrapper.text()).toContain('Add comment')
      wrapper.unmount()
    }
  })
  it('layers SoW scope and gene changes into existing Group Definition sections', () => {
    const comparison = { mode: 'previous_review_round', rows: { genes: [] }, changes: [{ section: 'scope_description', before: 'Earlier scope', after: 'Revised scope' }] }
    const wrapper = render(DefinitionReview, 'vcep', comparison)
    expect(wrapper.findAllComponents(ReviewSection).map(c => c.props('name'))).toContain('scope')
    expect(wrapper.get('del').text()).toContain('Earlier')
    expect(wrapper.get('ins').text()).toContain('Revised')
    expect(wrapper.findComponent({ name: 'VcepGeneList' }).props('scopeComparison')).toEqual(comparison)
    expect(wrapper.findComponent({ name: 'ReviewRoundDetail' }).exists()).toBe(false)
  })
})
