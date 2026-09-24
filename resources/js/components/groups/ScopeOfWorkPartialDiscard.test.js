import { mount, flushPromises } from '@vue/test-utils'
import { computed, effectScope, reactive, ref } from 'vue'
import { api } from '@/http'
import { useScopeOfWorkComparison } from '@/composables/scope_of_work_comparison'
import { describe, it, expect, vi, afterEach } from 'vitest'
import ScopeOfWorkStatusBanner from './ScopeOfWorkStatusBanner.vue'
import GroupDetail from '@/views/groups/GroupDetail.vue'
import ScopeOfWorkGeneDiscard from './ScopeOfWorkGeneDiscard.vue'
import ScopeOfWorkMemberDiscard from './ScopeOfWorkMemberDiscard.vue'

// The handler tests do not use application-step definitions. Isolate their
// unrelated circular domain imports while exercising the actual page methods.
vi.mock('@/domain/application_definitions', () => ({
  ApplicationSection: class {}, ApplicationStep: class {}, ApplicationDefinition: class {},
  GcepApplication: {}, VcepApplication: {}, ScvcepApplication: {}, applicationDefinitionFactory: vi.fn(),
}))

const status = (state = 'draft') => ({
  versioning_applicable: true,
  has_approved_version: true, has_active_revision: true,
  active_revision: { uuid: 'revision', version_label: '2.0', status: state,
    summary: { requires_submission: true },
    changes: [
      { id: 1, rule_key: 'panel_name.rename', label: 'Rename', can_discard: true },
      { id: 2, rule_key: 'scope_description.update', label: 'Scope', can_discard: true },
      { id: 3, rule_key: 'gene.add', label: 'Gene', can_discard: true },
      { id: 4, rule_key: 'member.add', label: 'Member', can_discard: false },
    ],
  },
})
const banner = props => mount(ScopeOfWorkStatusBanner, { props,
  global: { stubs: { SubmissionConfirmationModal: true } } })

afterEach(() => vi.restoreAllMocks())

describe('partial discard banner', () => {
  it('hides premature approved and draft records when versioning is inapplicable', () => {
    const wrapper = banner({ status: { ...status(), versioning_applicable: false } })
    expect(wrapper.text()).toBe('')
    expect(wrapper.findAll('button')).toHaveLength(0)
    wrapper.unmount()
  })
  it.each(['draft', 'revisions_requested'])('uses server capability for all change types in %s', state => {
    const payload = status(state)
    const rules = ['panel_name.rename', 'scope_description.update', 'gene.add', 'gene.remove', 'gene.update', 'gene.update_tier',
      'member.add', 'member.remove', 'member.update_role', 'member.add_chair', 'member.remove_chair', 'member.retire', 'member.unretire']
    payload.active_revision.changes = rules.map((rule_key, id) => ({ id, rule_key, label: rule_key, can_discard: true }))
    payload.active_revision.changes.push({ id: 99, rule_key: 'unsupported', label: 'Unsupported', can_discard: false })
    const wrapper = banner({ status: payload })
    expect(wrapper.findAll('li button')).toHaveLength(rules.length)
    expect(wrapper.findAll('li').at(-1).find('button').exists()).toBe(false)
    wrapper.unmount()
  })
  it.each(['draft', 'revisions_requested'])('offers only supported rows in %s', async state => {
    const payload = status(state)
    const wrapper = banner({ status: payload })
    const buttons = wrapper.findAll('li button')
    expect(buttons).toHaveLength(3)
    expect(wrapper.text()).toContain('Discard all changes')
    await buttons[0].trigger('click')
    expect(wrapper.emitted('discard-change')[0]).toEqual([{ revision: payload.active_revision, changeId: 1, fromBanner: true }])
    wrapper.unmount()
  })

  it('hides row discard while submitted or unauthorized', async () => {
    const wrapper = banner({ status: status('submitted') })
    expect(wrapper.findAll('li button')).toHaveLength(0)
    const payload = status()
    payload.active_revision.changes.forEach(change => { change.can_discard = false })
    await wrapper.setProps({ status: payload })
    expect(wrapper.findAll('li button')).toHaveLength(0)
    wrapper.unmount()
  })

  it('disables all mutations during discard and enables minor revisions-requested finalization', async () => {
    const payload = status('revisions_requested')
    const wrapper = banner({ status: payload, discarding: true })
    expect(wrapper.findAll('button').every(button => button.element.disabled)).toBe(true)
    const minor = status('revisions_requested')
    minor.active_revision.summary = { requires_submission: false, can_finalize_without_approval: true }
    await wrapper.setProps({ status: minor })
    const finalize = wrapper.findAll('button').find(button => button.text().startsWith('Finalize'))
    expect(finalize.element.disabled).toBe(true)
    await wrapper.setProps({ discarding: false })
    await finalize.trigger('click')
    expect(wrapper.emitted('finalize')).toHaveLength(1)
    wrapper.unmount()
  })
})

const context = () => ({
  scopeOfWorkStatus: status(),
  group: { uuid: 'group' }, discardingScopeOfWork: false, scopeOfWorkHasActiveEdits: false,
  scopeOfWorkHistory: { versions: [] }, getGroup: vi.fn().mockResolvedValue(),
  $refs: { groupGeneListRef: { refreshGenes: vi.fn().mockResolvedValue() } },
  $store: { dispatch: vi.fn().mockResolvedValue({ has_active_revision: true }), commit: vi.fn() },
})
const discard = ctx => GroupDetail.methods.discardScopeOfWorkChange.call(ctx, { revision: { uuid: 'revision' }, changeId: 1 })

describe('Group Detail partial discard', () => {
  it('uses read-only status and never posts refresh for an incomplete application', async () => {
    const ctx = context()
    ctx.group.is_ep = true
    ctx.$store.dispatch.mockResolvedValue({ ...status(), versioning_applicable: false })
    await GroupDetail.methods.refreshScopeOfWorkStatus.call(ctx)
    expect(ctx.$store.dispatch).toHaveBeenCalledExactlyOnceWith('groups/getScopeOfWorkStatus', 'group')
    expect(GroupDetail.computed.scopeOfWorkIsUnderReview.call(ctx)).toBe(false)
  })

  it('rechecks backend applicability before refreshing completed applications', async () => {
    const ctx = context()
    ctx.group.is_ep = true
    ctx.$store.dispatch.mockResolvedValue(status())
    await GroupDetail.methods.refreshScopeOfWorkStatus.call(ctx)
    expect(ctx.$store.dispatch.mock.calls.map(call => call[0])).toEqual(['groups/getScopeOfWorkStatus', 'groups/refreshScopeOfWorkStatus'])
  })

  it('suppresses contextual controls and direct UI mutations despite erroneous records', async () => {
    const ctx = reactive(context())
    ctx.scopeOfWorkStatus = { ...status(), versioning_applicable: false }
    const revision = ctx.scopeOfWorkStatus.active_revision
    const shared = GroupDetail.provide.call(ctx)
    expect(shared.scopeOfWorkMemberDiscard.status.value).toBeNull()
    expect(shared.scopeOfWorkGeneDiscard.status.value).toBeNull()
    const wrapper = mount(ScopeOfWorkMemberDiscard, { props: { personId: 1, operation: 'added' }, global: { provide: shared } })
    expect(wrapper.find('button').exists()).toBe(false)
    await discard(ctx)
    for (const method of ['submitScopeOfWorkRevision', 'finalizeScopeOfWorkRevision', 'discardScopeOfWorkRevision',
      'approveScopeOfWorkRevision', 'requestScopeOfWorkRevisionChanges']) {
      await GroupDetail.methods[method].call(ctx, revision)
    }
    expect(ctx.$store.dispatch).not.toHaveBeenCalled()
    wrapper.unmount()
  })
  it('shares banner/contextual controls, confirmation, busy protection and refresh', async () => {
    vi.spyOn(window, 'confirm').mockReturnValue(true)
    const ctx = reactive(context())
    ctx.scopeOfWorkStatus = status()
    ctx.scopeOfWorkStatus.active_revision.base_version = { version_label: '1.0' }
    ctx.scopeOfWorkStatus.active_revision.changes = [
      { id: 3, rule_key: 'gene.add', label: 'Add gene', entity_label: 'SCN1B', can_discard: true, after_value: { id: 30 } },
      { id: 4, rule_key: 'member.remove', label: 'Remove member', entity_label: 'Jane Smith', can_discard: true, before_value: { person_id: 40 } },
    ]
    const shared = { status: computed(() => ctx.scopeOfWorkStatus), busy: computed(() => ctx.discardingScopeOfWork),
      discard: payload => GroupDetail.methods.discardScopeOfWorkChange.call(ctx, payload) }
    const wrapper = mount({ components: { ScopeOfWorkStatusBanner, ScopeOfWorkGeneDiscard, ScopeOfWorkMemberDiscard },
      setup: () => ({ ctx, shared }),
      provide: () => ({ scopeOfWorkGeneDiscard: shared, scopeOfWorkMemberDiscard: shared }),
      template: `<div><ScopeOfWorkStatusBanner :status="ctx.scopeOfWorkStatus" :discarding="ctx.discardingScopeOfWork" @discard-change="shared.discard" />
        <ScopeOfWorkGeneDiscard :gene-id="30" /><ScopeOfWorkMemberDiscard :person-id="40" operation="removed" /></div>`,
    }, { global: { stubs: { SubmissionConfirmationModal: true } } })
    expect(wrapper.findAll('li button')).toHaveLength(2)
    expect(wrapper.findAll('button[aria-label^="Discard"]')).toHaveLength(2)
    let finish
    ctx.$store.dispatch.mockImplementationOnce(() => new Promise(resolve => { finish = resolve }))
    await wrapper.findAll('li button')[1].trigger('click')
    expect(window.confirm).toHaveBeenCalledWith(expect.stringMatching(/Remove member - Jane Smith[\s\S]*version 1.0/))
    expect(wrapper.findAll('button').every(button => button.element.disabled)).toBe(true)
    await shared.discard({ revision: ctx.scopeOfWorkStatus.active_revision, changeId: 3, geneLabel: 'SCN1B' })
    await GroupDetail.methods.finalizeScopeOfWorkRevision.call(ctx, ctx.scopeOfWorkStatus.active_revision)
    expect(ctx.$store.dispatch).toHaveBeenCalledTimes(1)
    expect(ctx.$store.dispatch).toHaveBeenCalledWith('groups/discardScopeOfWorkChange', { groupUuid: 'group', revisionUuid: 'revision', changeId: 4 })
    finish(ctx.scopeOfWorkStatus)
    await flushPromises()
    expect(ctx.getGroup).toHaveBeenCalledWith(true)
    expect(ctx.$refs.groupGeneListRef.refreshGenes).toHaveBeenCalledOnce()
    expect(ctx.scopeOfWorkHistory).toBeNull()
    ctx.scopeOfWorkStatus.active_revision.status = 'submitted'
    await flushPromises()
    expect(wrapper.findAll('li button')).toHaveLength(0)
    expect(wrapper.findAll('button[aria-label^="Discard"]')).toHaveLength(0)
    wrapper.unmount()
  })

  it('refreshes status on a banner 409 and blocks a stale banner token before confirmation', async () => {
    vi.spyOn(window, 'confirm').mockReturnValue(true)
    const ctx = context()
    ctx.scopeOfWorkStatus = status()
    const payload = { revision: ctx.scopeOfWorkStatus.active_revision, changeId: 3, fromBanner: true }
    ctx.$store.dispatch.mockRejectedValueOnce({ response: { status: 409 } }).mockResolvedValueOnce(status())
    await GroupDetail.methods.discardScopeOfWorkChange.call(ctx, payload)
    expect(ctx.$store.dispatch).toHaveBeenLastCalledWith('groups/getScopeOfWorkStatus', 'group')
    expect(ctx.$store.commit).toHaveBeenCalledWith('pushError', expect.stringContaining('retry'))
    expect(ctx.getGroup).not.toHaveBeenCalled()
    window.confirm.mockClear()
    ctx.scopeOfWorkStatus.active_revision.changes = []
    await GroupDetail.methods.discardScopeOfWorkChange.call(ctx, payload)
    expect(window.confirm).not.toHaveBeenCalled()
  })
  it('reloads contextual comparison through the existing status watcher after success', async () => {
    vi.spyOn(window, 'confirm').mockReturnValue(true)
    const get = vi.spyOn(api, 'get')
      .mockResolvedValueOnce({ data: { rows: { genes: [{ key: '2', operation: 'added' }] } } })
      .mockResolvedValueOnce({ data: { rows: { genes: [] } } })
    const revisionStatus = ref(status())
    const scope = effectScope()
    const comparison = scope.run(() => useScopeOfWorkComparison(() => [
      'group', revisionStatus.value.active_revision.uuid, 'live', revisionStatus.value,
    ]))
    await flushPromises()
    expect(comparison.comparison.value.rows.genes).toHaveLength(1)
    const ctx = context()
    Object.defineProperty(ctx, 'scopeOfWorkStatus', { get: () => revisionStatus.value,
      set: value => { revisionStatus.value = value } })
    ctx.$store.dispatch.mockResolvedValueOnce(status())
    await discard(ctx)
    await flushPromises()
    expect(get).toHaveBeenCalledTimes(2)
    expect(comparison.comparison.value.rows.genes).toEqual([])
    scope.stop()
  })
  it('confirms, posts, reloads and invalidates history', async () => {
    vi.spyOn(window, 'confirm').mockReturnValue(true)
    const ctx = context()
    await discard(ctx)
    expect(window.confirm).toHaveBeenCalledOnce()
    expect(ctx.$store.dispatch).toHaveBeenCalledWith('groups/discardScopeOfWorkChange', {
      groupUuid: 'group', revisionUuid: 'revision', changeId: 1,
    })
    expect(ctx.getGroup).toHaveBeenCalledOnce()
    expect(ctx.$refs.groupGeneListRef.refreshGenes).toHaveBeenCalledOnce()
    expect(ctx.scopeOfWorkHistory).toBeNull()
    expect(ctx.discardingScopeOfWork).toBe(false)
  })

  it('does not discard or reload active edits, or after canceled confirmation', async () => {
    vi.spyOn(window, 'confirm').mockReturnValue(false)
    const ctx = context()
    ctx.scopeOfWorkHasActiveEdits = true
    await discard(ctx)
    expect(window.confirm).not.toHaveBeenCalled()
    ctx.scopeOfWorkHasActiveEdits = false
    await discard(ctx)
    expect(ctx.$store.dispatch).not.toHaveBeenCalled()
    expect(ctx.getGroup).not.toHaveBeenCalled()
  })

  it('refreshes only status on 409 and tells the user to retry', async () => {
    vi.spyOn(window, 'confirm').mockReturnValue(true)
    const ctx = context()
    ctx.$store.dispatch.mockRejectedValueOnce({ response: { status: 409 } })
    await discard(ctx)
    expect(ctx.$store.dispatch).toHaveBeenLastCalledWith('groups/getScopeOfWorkStatus', 'group')
    expect(ctx.getGroup).not.toHaveBeenCalled()
    expect(ctx.$store.commit).toHaveBeenCalledWith('pushError', expect.stringContaining('retry'))
    expect(ctx.discardingScopeOfWork).toBe(false)
  })

  it('does not overwrite an editor opened while the request was pending', async () => {
    vi.spyOn(window, 'confirm').mockReturnValue(true)
    const ctx = context()
    let finish
    ctx.$store.dispatch.mockImplementationOnce(() => new Promise(resolve => { finish = resolve }))
    const request = discard(ctx)
    expect(ctx.discardingScopeOfWork).toBe(true)
    ctx.scopeOfWorkHasActiveEdits = true
    finish({ has_active_revision: false })
    await request
    expect(ctx.getGroup).not.toHaveBeenCalled()
  })

  it('confirms the gene and approved version and blocks duplicate/competing mutations', async () => {
    vi.spyOn(window, 'confirm').mockReturnValue(true)
    const ctx = context()
    let finish
    ctx.$store.dispatch.mockImplementationOnce(() => new Promise(resolve => { finish = resolve }))
    const request = GroupDetail.methods.discardScopeOfWorkChange.call(ctx, {
      revision: { uuid: 'revision', base_version: { version_label: '1.0' } }, changeId: 20, geneLabel: 'SCN1B',
    })
    expect(window.confirm).toHaveBeenCalledWith(expect.stringMatching(/SCN1B.*version 1.0/))
    await discard(ctx)
    await GroupDetail.methods.finalizeScopeOfWorkRevision.call(ctx, { uuid: 'revision' })
    await GroupDetail.methods.submitScopeOfWorkRevision.call(ctx, { revision: { uuid: 'revision' } })
    expect(ctx.$store.dispatch).toHaveBeenCalledTimes(1)
    finish({ has_active_revision: false })
    await request
    expect(ctx.scopeOfWorkStatus.has_active_revision).toBe(false)
    expect(ctx.$refs.groupGeneListRef.refreshGenes).toHaveBeenCalledOnce()
  })
})
