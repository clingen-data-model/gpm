import { mount } from '@vue/test-utils'
import { describe, it, expect, vi, afterEach } from 'vitest'
import ScopeOfWorkStatusBanner from './ScopeOfWorkStatusBanner.vue'
import GroupDetail from '@/views/groups/GroupDetail.vue'

// The handler tests do not use application-step definitions. Isolate their
// unrelated circular domain imports while exercising the actual page methods.
vi.mock('@/domain/application_definitions', () => ({
  ApplicationSection: class {}, ApplicationStep: class {}, ApplicationDefinition: class {},
  GcepApplication: {}, VcepApplication: {}, ScvcepApplication: {}, applicationDefinitionFactory: vi.fn(),
}))

const status = (state = 'draft') => ({
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
  it.each(['draft', 'revisions_requested'])('offers only supported rows in %s', async state => {
    const payload = status(state)
    const wrapper = banner({ status: payload })
    const buttons = wrapper.findAll('li button')
    expect(buttons).toHaveLength(2)
    expect(wrapper.text()).toContain('Discard all changes')
    await buttons[0].trigger('click')
    expect(wrapper.emitted('discard-change')[0]).toEqual([{ revision: payload.active_revision, changeId: 1 }])
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
  group: { uuid: 'group' }, discardingScopeOfWork: false, scopeOfWorkHasActiveEdits: false,
  scopeOfWorkHistory: { versions: [] }, getGroup: vi.fn().mockResolvedValue(),
  $store: { dispatch: vi.fn().mockResolvedValue({ has_active_revision: true }), commit: vi.fn() },
})
const discard = ctx => GroupDetail.methods.discardScopeOfWorkChange.call(ctx, { revision: { uuid: 'revision' }, changeId: 1 })

describe('Group Detail partial discard', () => {
  it('confirms, posts, reloads and invalidates history', async () => {
    vi.spyOn(window, 'confirm').mockReturnValue(true)
    const ctx = context()
    await discard(ctx)
    expect(window.confirm).toHaveBeenCalledOnce()
    expect(ctx.$store.dispatch).toHaveBeenCalledWith('groups/discardScopeOfWorkChange', {
      groupUuid: 'group', revisionUuid: 'revision', changeId: 1,
    })
    expect(ctx.getGroup).toHaveBeenCalledOnce()
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
})
