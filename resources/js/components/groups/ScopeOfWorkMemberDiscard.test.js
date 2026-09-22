import { mount, flushPromises } from '@vue/test-utils'
import { ref, effectScope } from 'vue'
import { describe, it, expect, vi, afterEach } from 'vitest'
import ScopeOfWorkMemberDiscard from './ScopeOfWorkMemberDiscard.vue'
import ScopeOfWorkMemberRoles from './ScopeOfWorkMemberRoles.vue'
import ScopeOfWorkStatusBanner from './ScopeOfWorkStatusBanner.vue'
import GroupDetail from '@/views/groups/GroupDetail.vue'
import { api } from '@/http'
import { useScopeOfWorkComparison } from '@/composables/scope_of_work_comparison'

vi.mock('@/domain/application_definitions', () => ({
  ApplicationSection: class {}, ApplicationStep: class {}, ApplicationDefinition: class {},
  GcepApplication: {}, VcepApplication: {}, ScvcepApplication: {}, applicationDefinitionFactory: vi.fn(),
}))
afterEach(() => vi.restoreAllMocks())

const status = () => ({ has_approved_version: true, has_active_revision: true, active_revision: {
  uuid: 'revision', status: 'draft', base_version: { version_label: '1.0' }, summary: {},
  changes: [{ id: 7, rule_key: 'member.remove_chair', can_discard: true,
    entity_label: 'Alex Smith', before_value: { person_id: 10, membership_id: 20, role: 'chair', role_id: 102 }, after_value: null }],
} })

describe('member discard controls', () => {
  it('matches by person and role, blocks busy clicks, and drops stale or submitted actions', async () => {
    const context = { status: ref(status()), busy: ref(false), discard: vi.fn() }
    const wrapper = mount(ScopeOfWorkMemberDiscard, { props: { personId: 10, kind: 'role', roleName: 'chair', roleLabel: 'Chair', operation: 'removed' },
      global: { provide: { scopeOfWorkMemberDiscard: context } } })
    await wrapper.get('button').trigger('click')
    expect(context.discard).toHaveBeenCalledWith(expect.objectContaining({ changeId: 7,
      memberChange: { kind: 'role', label: 'Alex Smith', roleLabel: 'Chair', operation: 'removed' } }))
    context.busy.value = true
    await flushPromises()
    await wrapper.get('button').trigger('click')
    expect(context.discard).toHaveBeenCalledTimes(1)
    await wrapper.setProps({ personId: 11 })
    expect(wrapper.find('button').exists()).toBe(false)
    await wrapper.setProps({ personId: 10 })
    context.status.value.active_revision.changes = []
    await flushPromises()
    expect(wrapper.find('button').exists()).toBe(false)
    context.status.value = status()
    context.status.value.active_revision.status = 'submitted'
    await flushPromises()
    expect(wrapper.find('button').exists()).toBe(false)
    wrapper.unmount()
  })

  it.each(['added', 'removed'])('does not repeat role discards for a wholly %s member', operation => {
    const context = { status: ref(status()), busy: ref(false), discard: vi.fn() }
    const wrapper = mount(ScopeOfWorkMemberRoles, { props: { allowDiscard: true, personId: 10,
      roles: [{ name: 'chair', display_name: 'Chair' }], comparison: { operation, roles_available: true,
        roles: [{ key: 'chair', operation: 'removed', before: { label: 'Chair' } }] } },
      global: { provide: { scopeOfWorkMemberDiscard: context } } })
    expect(wrapper.find('button').exists()).toBe(false)
    wrapper.unmount()
  })

  it('also exposes the supported contextual action in the banner', () => {
    const wrapper = mount(ScopeOfWorkStatusBanner, { props: { status: status() }, global: { stubs: { SubmissionConfirmationModal: true } } })
    expect(wrapper.findAll('li button')).toHaveLength(1)
    expect(wrapper.text()).toContain('Discard all changes')
  })
})

describe('member discard page handler', () => {
  const context = () => ({ group: { uuid: 'group' }, scopeOfWorkStatus: status(),
    discardingScopeOfWork: false, scopeOfWorkMutationBusy: false, scopeOfWorkHasActiveEdits: false,
    scopeOfWorkHistory: { versions: [] }, getGroup: vi.fn().mockResolvedValue(),
    $store: { dispatch: vi.fn().mockResolvedValue(status()), commit: vi.fn() },
  })
  const payload = () => ({ revision: status().active_revision, changeId: 7,
    memberChange: { kind: 'role', label: 'Alex Smith', roleLabel: 'Chair', operation: 'removed' } })

  it('confirms the captured role/base version, blocks duplicates, reloads members and invalidates history', async () => {
    vi.spyOn(window, 'confirm').mockReturnValue(true)
    const ctx = context()
    let finish
    ctx.$store.dispatch.mockImplementationOnce(() => new Promise(resolve => { finish = resolve }))
    const request = GroupDetail.methods.discardScopeOfWorkChange.call(ctx, payload())
    await GroupDetail.methods.discardScopeOfWorkChange.call(ctx, payload())
    expect(ctx.$store.dispatch).toHaveBeenCalledTimes(1)
    expect(window.confirm).toHaveBeenCalledWith(expect.stringMatching(/removal of Chair for Alex Smith.*version 1.0/))
    finish(status())
    await request
    expect(ctx.getGroup).toHaveBeenCalledWith(true)
    expect(ctx.scopeOfWorkHistory).toBeNull()
    expect(ctx.discardingScopeOfWork).toBe(false)
  })

  it('rejects stale IDs and cancelled confirmation without dispatch', async () => {
    vi.spyOn(window, 'confirm').mockReturnValue(false)
    const ctx = context()
    await GroupDetail.methods.discardScopeOfWorkChange.call(ctx, { ...payload(), changeId: 999 })
    expect(window.confirm).not.toHaveBeenCalled()
    await GroupDetail.methods.discardScopeOfWorkChange.call(ctx, payload())
    expect(ctx.$store.dispatch).not.toHaveBeenCalled()
  })

  it('refreshes member comparison through the existing shared status watcher', async () => {
    vi.spyOn(window, 'confirm').mockReturnValue(true)
    const get = vi.spyOn(api, 'get').mockResolvedValueOnce({ data: { rows: { members: [{ key: '10', operation: 'changed' }] } } })
      .mockResolvedValueOnce({ data: { rows: { members: [{ key: '10', operation: 'unchanged' }] } } })
    const current = ref(status())
    const scope = effectScope()
    const comparison = scope.run(() => useScopeOfWorkComparison(() => ['group', 'revision', 'live', current.value]))
    await flushPromises()
    const ctx = context()
    Object.defineProperty(ctx, 'scopeOfWorkStatus', { get: () => current.value, set: value => { current.value = value } })
    await GroupDetail.methods.discardScopeOfWorkChange.call(ctx, payload())
    await flushPromises()
    expect(get).toHaveBeenCalledTimes(2)
    expect(comparison.comparison.value.rows.members[0].operation).toBe('unchanged')
    scope.stop()
  })
})
