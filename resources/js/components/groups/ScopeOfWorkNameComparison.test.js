import { mount } from '@vue/test-utils'
import { ref, nextTick } from 'vue'
import { describe, it, expect } from 'vitest'
import ScopeOfWorkStatusBanner from './ScopeOfWorkStatusBanner.vue'
import ScopeOfWorkNameComparison from './ScopeOfWorkNameComparison.vue'

describe('Scope of Work name comparison', () => {
  it('renders a live draft name in inline-title mode with the existing styles', () => {
    const comparison = ref({ source: 'live', mode: 'approved_baseline', unavailable_sections: [],
      changes: [{ section: 'group.name', before: 'FBN1-TEST', after: 'FBN1' }] })
    const wrapper = mount(ScopeOfWorkNameComparison, { props: { variant: 'inline-title' },
      global: { provide: { scopeOfWorkComparisonState: { comparison } } } })
    expect(wrapper.get('del').text()).toBe('FBN1-TEST')
    expect(wrapper.get('del').classes()).toContain('text-red-700')
    expect(wrapper.get('ins').text()).toBe('FBN1')
    expect(wrapper.get('ins').classes()).toContain('text-green-800')
    wrapper.unmount()
  })
  it('shows captured old and new names in the blue banner, including drafts', () => {
    const wrapper = mount(ScopeOfWorkStatusBanner, { props: { status: {
      has_approved_version: true, has_active_revision: true,
      active_revision: { status: 'draft', version_label: '2.0', summary: {}, changes: [{ id: 1, rule_key: 'panel_name.rename', label: 'Rename Expert Panel', entity_label: 'FBN1', before_value: { value: 'FBN1-TEST' }, after_value: { value: 'FBN1' }, requires_approval: 'yes' }] },
    } }, global: { stubs: { SubmissionConfirmationModal: true } } })
    expect(wrapper.text()).toContain('FBN1-TEST to FBN1')
    expect(wrapper.text()).toContain('requires approval')
  })
  it('shows the ordinary name only when no comparison exists', async () => {
    const comparison = ref({ changes: [] })
    const wrapper = mount(ScopeOfWorkNameComparison, { props: { variant: 'inline-title' }, slots: { default: 'Live name' }, global: { provide: { scopeOfWorkComparisonState: { comparison } } } })
    expect(wrapper.text()).toBe('Live name')
    comparison.value = { changes: [{ section: 'group.name', before: 'Old', after: 'Submitted name' }] }
    await nextTick()
    expect(wrapper.text()).not.toContain('Live name')
    expect(wrapper.text()).toContain('Submitted name')
    expect(wrapper.text()).toBe('Old Submitted name')
    expect(wrapper.text()).not.toContain('Previous:')
    expect(wrapper.text()).not.toContain('Submitted:')
    expect(wrapper.get('del').text()).toBe('Old')
    expect(wrapper.get('ins').text()).toBe('Submitted name')
    expect(wrapper.find('.text-sm').exists()).toBe(false)
    expect(wrapper.find('.font-normal').exists()).toBe(false)
  })
  it('renders nothing without a provider', () => {
    expect(mount(ScopeOfWorkNameComparison).find('section').exists()).toBe(false)
  })
  it('shows only available changed names with escaped values and mode headings', async () => {
    const comparison = ref({ mode: 'approved_baseline', changes: [
      { section: 'group.name', before: 'Old name', after: '<img> New name' },
      { section: 'expert_panel.long_base_name', before: 'Long old', after: 'Long new' },
      { section: 'expert_panel.short_base_name', before: 'Same', after: 'Same' },
      { section: 'scope_description', before: 'Ignore', after: 'Ignored' },
      { section: 'toString', before: 'Ignore', after: 'Ignored' },
    ] })
    const wrapper = mount(ScopeOfWorkNameComparison, { global: { provide: { scopeOfWorkComparisonState: { comparison } } } })
    expect(wrapper.findAll('del')).toHaveLength(1)
    expect(wrapper.text()).not.toContain('Long old')
    expect(wrapper.text()).toContain('Name changes since approved baseline')
    expect(wrapper.text()).toContain('Previous:')
    expect(wrapper.text()).toContain('Submitted:')
    expect(wrapper.get('del').text()).toBe('Old name')
    expect(wrapper.get('ins').text()).toBe('<img> New name')
    expect(wrapper.find('img').exists()).toBe(false)
    comparison.value = { ...comparison.value, unavailable_sections: ['group.name'] }
    await nextTick()
    expect(wrapper.get('del').text()).toBe('Long old')
    comparison.value = { ...comparison.value, mode: 'previous_review_round', unavailable_sections: ['group.name', 'expert_panel.long_base_name'] }
    await nextTick()
    expect(wrapper.find('del').exists()).toBe(false)
    comparison.value = { ...comparison.value, unavailable_sections: [] }
    await nextTick()
    expect(wrapper.text()).toContain('Name changes since previous review round')
    comparison.value = { changes: [] }
    await nextTick()
    expect(wrapper.find('del').exists()).toBe(false)
  })
})
