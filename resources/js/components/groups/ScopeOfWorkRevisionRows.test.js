import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import { scopeOfWorkRevisionRows, revisionFieldLabel } from '@/scope_of_work_revision_rows'
import ScopeOfWorkHistory from './ScopeOfWorkHistory.vue'
import ScopeOfWorkStatusBanner from './ScopeOfWorkStatusBanner.vue'

const gene = (field, before, after, extra = {}) => ({
  id: field, rule_key: field === 'tier' ? 'gene.update_tier' : 'gene.update',
  label: `Update gene: ${field}`, entity_label: 'NTRK3 - MONDO:2', field_name: field,
  before_value: { id: 10, [field]: before }, after_value: { id: 10, [field]: after },
  requires_approval: 'yes', ...extra,
})
const context = (oldId = 'MONDO:1', newId = 'MONDO:2', oldName = 'Old disease', newName = 'New disease') => ({
  before: { gene_symbol: 'NTRK3', mondo_id: oldId, disease_name: oldName },
  after: { gene_symbol: 'NTRK3', mondo_id: newId, disease_name: newName },
})
const descriptions = (changes, type = 'vcep') => scopeOfWorkRevisionRows(changes, type).map(row => row.description)
const version = changes => ({ uuid: 'v', status: 'draft', summary: {}, changes })
const history = changes => mount(ScopeOfWorkHistory, { props: { panelType: 'vcep', history: { versions: [version(changes)] } },
  global: { mocks: { formatDate: () => '' } } })

describe('revision descriptions', () => {
  it.each([['Old', 'New'], [{ value: 'Old' }, { value: 'New' }]])('uses captured names', (before_value, after_value) => {
    expect(descriptions([{ rule_key: 'panel_name.rename', before_value, after_value }]))
      .toEqual(['Rename Expert Panel — "Old" → "New"'])
  })
  it.each([['vcep', 'Current → Future'], ['scvcep', 'Current → Future'], ['gcep', 'Primary → Secondary']])('formats %s tiers', (type, pair) => {
    expect(descriptions([gene('tier', 1, '2')], type)).toEqual([`Update gene tier — NTRK3 - MONDO:2: ${pair}`])
  })
  it('combines disease fields into one rendered entry with complete pairs and approval', () => {
    const changes = [gene('mondo_id', 'MONDO:1', 'MONDO:2'), gene('disease_name', 'Old disease', 'New disease')]
    const original = JSON.stringify(changes)
    const wrapper = history(changes)
    expect(wrapper.findAll('li')).toHaveLength(1)
    expect(wrapper.find('li').text().replace(/\s+/g, ' ')).toBe('Update gene disease — NTRK3: MONDO:1 Old disease → MONDO:2 New disease — required approval')
    expect(wrapper.text()).not.toMatch(/Mondo Id|Disease Name/)
    expect(JSON.stringify(changes)).toBe(original)
  })
  it.each([
    ['disease_name', 'Old disease', 'New disease', context('MONDO:1', 'MONDO:1'), 'MONDO:1 Old disease → MONDO:1 New disease'],
    ['mondo_id', 'MONDO:1', 'MONDO:2', context('MONDO:1', 'MONDO:2', 'Disease', 'Disease'), 'MONDO:1 Disease → MONDO:2 Disease'],
  ])('uses captured companion values for %s-only edits', (field, before, after, display_gene, pair) => {
    expect(descriptions([gene(field, before, after, { display_gene })])).toEqual([`Update gene disease — NTRK3: ${pair}`])
  })
  it('does not invent missing companion data or group different genes with the same symbol', () => {
    const changes = [gene('mondo_id', 'MONDO:1', 'MONDO:2'), gene('disease_name', 'Old', 'New', {
      before_value: { id: 11, disease_name: 'Old' }, after_value: { id: 11, disease_name: 'New' },
    })]
    expect(descriptions(changes)).toEqual(['Update gene disease — NTRK3: MONDO:1 → MONDO:2', 'Update gene disease — NTRK3: Old → New'])
    expect(revisionFieldLabel('mondo_id')).toBe('MONDO ID')
  })
  it.each(['add', 'remove'])('keeps %s concise', operation => {
    const label = operation === 'add' ? 'Add gene' : 'Remove gene'
    expect(descriptions([{ rule_key: `gene.${operation}`, label, entity_label: 'NTRK3 - MONDO:1', before_value: {}, after_value: {} }]))
      .toEqual([`${label} — NTRK3 - MONDO:1`])
  })
  it('formats other fields while leaving scope text out of the list', () => {
    expect(descriptions([gene('moi', 'AD', 'AR')])).toEqual(['Update gene: MOI — NTRK3 - MONDO:2: AD → AR'])
    expect(descriptions([{ rule_key: 'scope_description.update', label: 'Update Scope of Work description', entity_label: 'Panel', before_value: 'Long old text', after_value: 'Long new text' }]))
      .toEqual(['Update Scope of Work description — Panel'])
  })
  it('preserves conditional approval', () => {
    expect(history([gene('tier', 1, 2, { requires_approval: 'conditional' })]).find('li').text()).toContain('— conditional approval')
  })
  it('prefers explicit changed values and preserves approval when merging', () => {
    const changes = [gene('mondo_id', 'MONDO:1', 'MONDO:2', { display_gene: context(), requires_approval: 'conditional' }),
      gene('disease_name', 'Captured old', 'Captured new', { display_gene: context(), requires_approval: 'yes' })]
    const [row] = scopeOfWorkRevisionRows(changes, 'vcep')
    expect(row.description).toBe('Update gene disease — NTRK3: MONDO:1 Captured old → MONDO:2 Captured new')
    expect(row.requires_approval).toBe('yes')
  })
  it('keeps original single-field discard targets in a grouped banner row', async () => {
    const changes = [gene('mondo_id', 'MONDO:1', 'MONDO:2', { can_discard: true }), gene('disease_name', 'Old', 'New', { can_discard: true })]
    const revision = version(changes)
    const wrapper = mount(ScopeOfWorkStatusBanner, { props: { panelType: 'vcep', canManage: true, status: {
      versioning_applicable: true, has_approved_version: true, has_active_revision: true, active_revision: revision,
    } }, global: { stubs: { SubmissionConfirmationModal: true } } })
    expect(wrapper.findAll('li')).toHaveLength(1)
    const buttons = wrapper.findAll('li button')
    expect(buttons.map(button => button.text())).toEqual(['Discard MONDO ID', 'Discard Disease'])
    await buttons[0].trigger('click')
    expect(wrapper.emitted('discard-change')[0][0].changeId).toBe('mondo_id')
    expect(wrapper.find('li').text()).toContain('— requires approval')
  })
})
