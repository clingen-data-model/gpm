import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { ref } from 'vue'
import { scopeOfWorkChangeLabel } from '@/scope_of_work_change_label'
import SubmissionContextSummary from '../applications/Review/SubmissionContextSummary.vue'
vi.mock('vuex', () => ({ useStore: () => ({}) }))
vi.mock('@/auth_utils.js', () => ({ hasPermission: () => false }))
const rename = { rule_key: 'panel_name.rename', label: 'Rename Expert Panel', entity_label: 'FBN1' }
describe('Scope of Work change labels', () => {
  it('formats scalar and wrapped names with ASCII separators', () => {
    for (const values of [{ before_value: 'FBN1-TEST', after_value: 'FBN1' }, { before_value: { value: 'FBN1-TEST' }, after_value: { value: 'FBN1' } }]) {
      expect(scopeOfWorkChangeLabel({ ...rename, ...values })).toBe('Rename Expert Panel - FBN1-TEST to FBN1')
    }
  })
  it('preserves fallback details for incomplete and other changes', () => {
    expect(scopeOfWorkChangeLabel(rename)).toBe('Rename Expert Panel - FBN1')
    expect(scopeOfWorkChangeLabel({ ...rename, before_value: 'Old' })).toBe('Rename Expert Panel - FBN1')
    expect(scopeOfWorkChangeLabel({ label: 'Added gene', entity_label: 'GENE1' })).toBe('Added gene - GENE1')
    expect(scopeOfWorkChangeLabel({ label: 'Updated scope' })).toBe('Updated scope')
  })
  it('renders the same formatting in the reviewer blue box', () => {
    const wrapper = mount(SubmissionContextSummary, { global: {
      provide: { group: ref({ uuid: 'group' }), latestSubmission: ref({ id: 1, data: { context: 'scope_of_work_revision', changes: [{ ...rename, before_value: { value: 'FBN1-TEST' }, after_value: { value: 'FBN1' } }] } }) },
      stubs: { StaticAlert: { template: '<div><slot /></div>' } },
    } })
    expect(wrapper.text()).toContain('Rename Expert Panel - FBN1-TEST to FBN1')
  })
})
