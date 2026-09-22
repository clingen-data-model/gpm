import { mount, flushPromises } from '@vue/test-utils'
import { reactive, ref } from 'vue'
import { createStore } from 'vuex'
import { describe, it, expect, vi } from 'vitest'
import MemberList from './MemberList.vue'
import MemberPreview from './MemberPreview.vue'
import MemberForm from './MemberForm.vue'
import DataTable from '../DataTable.vue'
import GroupMember from '@/domain/group_member'
import { actions as groupActions } from '@/store/groups'
import { useScopeOfWorkMemberRows, memberRetirementTransition } from '@/composables/scope_of_work_member_rows'

vi.mock('@/composables/router_aware_sort_and_filter', () => ({ default: () => ({ sort: ref({ field: 'person.last_name', desc: false }), filter: ref('') }) }))
vi.mock('@/domain/application_definitions', () => ({
  ApplicationSection: class {}, ApplicationStep: class {}, ApplicationDefinition: class {},
  GcepApplication: {}, VcepApplication: {}, ScvcepApplication: {}, applicationDefinitionFactory: vi.fn(),
}))

const chair = { id: 102, name: 'chair', display_name: 'Chair' }
const coordinator = { id: 101, name: 'coordinator', display_name: 'Coordinator' }
const capturedRole = role => ({ id: role.id, name: role.name, label: role.display_name })
const liveMember = (id = 1, extra = {}) => new GroupMember({ id: id + 10, person_id: id,
  person: { id, uuid: `p${id}`, first_name: 'Alex', last_name: 'Smith', email: `alex${id}@example.org` },
  roles: [chair], ...extra })
const snapshot = (id = 1, extra = {}) => ({ id: id + 10, person_id: id, first_name: 'Alex', last_name: 'Smith',
  label: 'Alex Smith', email: `alex${id}@example.org`, roles: [capturedRole(chair)], end_date: null, ...extra })
const row = (id, operation, extra = {}) => ({ key: String(id), operation, before: operation === 'added' ? null : snapshot(id),
  after: operation === 'removed' ? null : snapshot(id), roles_available: true, roles: [], field_changes: [], ...extra })
const comparison = rows => ({ rows: { members: rows }, unavailable_sections: [] })

async function render(members = [liveMember()], scopeComparison = null, props = {}, discardContext = null) {
  const group = reactive({ id: 1, uuid: 'group', members, has_coi_requirement: true, isEp: () => false })
  const store = createStore({ state: { systemInfo: { env: 'local', app: { features: {} } } },
    getters: { 'groups/currentItemOrNew': () => group } })
  const dispatch = vi.spyOn(store, 'dispatch').mockResolvedValue()
  const push = vi.fn()
  const wrapper = mount(MemberList, { props: { scopeComparison, ...props }, global: {
    plugins: [store], components: { DataTable },
    // Sharing a provider elsewhere must not implicitly opt this list in.
    provide: { scopeOfWorkComparisonState: { comparison: ref(comparison([row(99, 'removed')])) }, scopeOfWorkMemberDiscard: discardContext },
    config: { warnHandler: () => {} },
    stubs: { MemberPreview: true, RouterLink: { template: '<a><slot /></a>' },
      'modal-dialog': true, 'dropdown-menu': { template: '<div class="member-menu"><slot name="label"/><slot/></div>' },
      popover: { template: '<div><slot/></div>' }, popper: { template: '<div><slot/></div>' } },
    mocks: { $route: { path: '/groups/group' }, $router: { push },
      hasAnyPermission: () => true, hasRole: () => true, append: (a, b) => `${a}/${b}`,
      yearAgo: () => new Date('2025-01-01'), formatDate: value => value ? String(value) : '' },
  } })
  await flushPromises()
  dispatch.mockClear()
  return { wrapper, group, dispatch, push }
}

describe('contextual member list', () => {
  it('offers only membership discard for whole additions/removals while history stays non-operational', async () => {
    const context = { status: ref({ active_revision: { uuid: 'revision', status: 'draft', changes: [
      { id: 1, rule_key: 'member.add', can_discard: true, after_value: { person_id: 1 } },
      { id: 2, rule_key: 'member.remove', can_discard: true, before_value: { person_id: 2 } },
    ] } }), busy: ref(false), discard: vi.fn() }
    const { wrapper } = await render([liveMember()], comparison([row(1, 'added'), row(2, 'removed')]), {}, context)
    const bodies = wrapper.findAll('tbody')
    expect(bodies[0].findAll('button[aria-label="Discard member change"]')).toHaveLength(1)
    expect(bodies[1].findAll('button[aria-label="Discard member change"]')).toHaveLength(1)
    expect(bodies[1].text()).not.toMatch(/Update membership|Remove from group|Complete COI/)
    expect(wrapper.vm.operationalMembers).toHaveLength(1)
    await bodies[1].get('button[aria-label="Discard member change"]').trigger('click')
    expect(context.discard).toHaveBeenCalledWith(expect.objectContaining({ changeId: 2 }))
    context.status.value.active_revision.status = 'submitted'
    await flushPromises()
    expect(wrapper.findAll('button[aria-label^="Discard"]')).toHaveLength(0)
    wrapper.unmount()
  })

  it('places discard beside changed roles and retirement, with no generic member discard', async () => {
    const context = { status: ref({ active_revision: { status: 'draft', changes: [
      { id: 1, rule_key: 'member.remove_chair', can_discard: true, before_value: { person_id: 1, role: 'chair' } },
      { id: 2, rule_key: 'member.update_role', can_discard: true, after_value: { person_id: 1, role: 'coordinator' } },
      { id: 3, rule_key: 'member.retire', can_discard: true, after_value: { person_id: 1, end_date: '2026-01-01' } },
    ] } }), busy: ref(false), discard: vi.fn() }
    const roles = [
      { key: 'chair', operation: 'removed', before: capturedRole(chair) },
      { key: 'coordinator', operation: 'added', after: capturedRole(coordinator) },
      { key: 'expert', operation: 'unchanged', after: { label: 'Expert' } },
    ]
    const { wrapper } = await render([liveMember()], comparison([row(1, 'changed', { roles,
      field_changes: [{ field: 'end_date', before: null, after: '2026-01-01' }] })]), {}, context)
    expect(wrapper.findAll('button[aria-label^="Discard"]')).toHaveLength(3)
    expect(wrapper.find('button[aria-label="Discard member change"]').exists()).toBe(false)
    expect(wrapper.find('button[aria-label="Discard Expert change"]').exists()).toBe(false)
    expect(wrapper.find('button[aria-label="Discard retirement change"]').exists()).toBe(true)
    wrapper.unmount()
  })
  it('preserves normal rendering and original objects without opt-in or with unavailable comparison', async () => {
    const { wrapper, group } = await render()
    expect(wrapper.vm.memberRows[0].member).toBe(group.members[0])
    expect(wrapper.find('tbody').text()).toContain('Chair')
    expect(wrapper.find('tbody').text()).not.toMatch(/Added|Removed|Changed/)
    await wrapper.setProps({ scopeComparison: { ...comparison([row(2, 'removed')]), unavailable_sections: ['members'] } })
    expect(wrapper.vm.memberRows).toHaveLength(1)
    expect(wrapper.find('tbody').text()).not.toContain('Removed')
    wrapper.unmount()
  })

  it('retains a removed row, badges additions, and excludes history from operations', async () => {
    const { wrapper, dispatch, push } = await render([liveMember()], comparison([row(1, 'added'), row(2, 'removed')]))
    const bodies = wrapper.findAll('tbody')
    expect(bodies).toHaveLength(2)
    expect(bodies[0].text()).toContain('Added')
    expect(bodies[0].text()).toContain('Update membership')
    expect(bodies[1].text()).toContain('Removed')
    expect(bodies[1].find('del').text()).toBe('Alex')
    expect(bodies[1].text()).not.toMatch(/Update membership|Remove from group|Complete COI|Unretire/)
    expect(wrapper.vm.filteredEmails.join()).not.toContain('alex2@')
    expect(wrapper.vm.exportUrl).toContain('member_ids=11')
    expect(wrapper.vm.exportUrl).not.toContain('12')
    const historical = wrapper.vm.memberRows[1]
    wrapper.vm.editMember(historical)
    wrapper.vm.confirmRetireMember(historical)
    wrapper.vm.confirmUnretire(historical)
    wrapper.vm.confirmRemoveMember(historical)
    wrapper.vm.goToMember(historical)
    wrapper.vm.selectedMember = historical
    await wrapper.vm.retireMember()
    await wrapper.vm.unretireMember()
    await wrapper.vm.removeMember()
    await wrapper.vm.adminCompleteCoi(historical)
    expect(dispatch).not.toHaveBeenCalled()
    expect(push).not.toHaveBeenCalled()
    wrapper.unmount()
  })

  it('guards readonly methods even for a live member', async () => {
    const { wrapper, dispatch, push } = await render([liveMember()], null, { readonly: true })
    const live = wrapper.vm.memberRows[0]
    wrapper.vm.editMember(live)
    wrapper.vm.confirmRemoveMember(live)
    wrapper.vm.selectedMember = live
    await wrapper.vm.retireMember()
    await wrapper.vm.unretireMember()
    await wrapper.vm.removeMember()
    await wrapper.vm.adminCompleteCoi(live)
    expect(dispatch).not.toHaveBeenCalled()
    expect(push).not.toHaveBeenCalled()
    wrapper.unmount()
  })

  it.each(['retireMember', 'unretireMember', 'removeMember'])('emits updated after %s succeeds', async method => {
    const { wrapper, dispatch } = await render()
    wrapper.vm.selectedMember = wrapper.vm.memberRows[0]
    await wrapper.vm[method]()
    expect(dispatch).toHaveBeenCalledOnce()
    expect(wrapper.emitted('updated')).toHaveLength(1)
    wrapper.unmount()
  })

  it('renders nested changes without a whole-member badge or contaminating live roles', async () => {
    const roleRows = [
      { key: 'chair', operation: 'removed', before: capturedRole(chair), after: null },
      { key: 'coordinator', operation: 'added', before: null, after: capturedRole(coordinator) },
    ]
    const { wrapper, group } = await render([liveMember(1, { roles: [coordinator] })], comparison([row(1, 'changed', { roles: roleRows })]))
    const cells = wrapper.find('tbody').findAll('td')
    expect(cells[2].text()).not.toMatch(/Added|Removed|Changed/)
    expect(cells[4].get('del').text()).toBe('Chair')
    expect(cells[4].get('ins').text()).toBe('Coordinator')
    expect(group.members[0].roles).toEqual([coordinator])
    expect(group.members[0].clone().roles.map(role => role.id)).toEqual([101])
    expect(Object.hasOwn(group.members[0], 'comparison')).toBe(false)
    wrapper.unmount()
  })

  it('keeps the current retirement change visible while hiding unrelated retired members', async () => {
    const retirement = { field: 'end_date', before: null, after: '2026-01-01' }
    const { wrapper } = await render(
      [1, 2, 3].map(id => liveMember(id, { end_date: '2026-01-01' })),
      comparison([row(1, 'changed', { field_changes: [retirement] }), row(2, 'unchanged')]),
    )
    expect(wrapper.vm.filters.hideAlumns).toBe(true)
    expect(wrapper.vm.filteredMembers.map(row => row.member.person_id)).toEqual([1])
    expect(wrapper.text()).not.toContain('hidden by retired-member filter')
    expect(wrapper.find('tbody').text()).toContain('Active → Retired')
    expect(memberRetirementTransition({ field_changes: [{ ...retirement, before: '2026-01-01', after: null }] })).toBe('Retired → Active')
    expect(memberRetirementTransition({ field_changes: [{ ...retirement, before: '2025-01-01' }] })).toBe('')
    expect(memberRetirementTransition({ field_changes: [retirement], unavailable_fields: ['end_date'] })).toBe('')
    wrapper.unmount()
  })

  it('keeps changed retired snapshot members visible in frozen comparison', async () => {
    const retirement = { field: 'end_date', before: null, after: '2026-01-01' }
    const { wrapper } = await render([], comparison([
      row(1, 'changed', { after: snapshot(1, { end_date: retirement.after }), field_changes: [retirement] }),
      row(2, 'unchanged', { after: snapshot(2, { end_date: retirement.after }) }),
    ]), { readonly: true })
    expect(wrapper.vm.filteredMembers.map(row => row.member.person_id)).toEqual([1])
    expect(wrapper.find('tbody').text()).toContain('Active → Retired')
    await wrapper.setData({ filters: { keyword: 'NotMatching' } })
    expect(wrapper.vm.filteredMembers).toHaveLength(0)
    wrapper.unmount()
  })

  it.each(['added', 'removed', 'changed'])('bypasses retirement filtering for a current %s member', async operation => {
    const { wrapper } = await render([liveMember(1, { end_date: '2026-01-01' })], comparison([row(1, operation)]))
    expect(wrapper.vm.filteredMembers).toHaveLength(1)
    await wrapper.setProps({ scopeComparison: null })
    expect(wrapper.vm.filteredMembers).toHaveLength(0)
    wrapper.unmount()
  })

  it('preserves normal retired filtering without contextual comparison', async () => {
    const { wrapper } = await render([liveMember(1), liveMember(2, { end_date: '2026-01-01' })])
    expect(wrapper.vm.filteredMembers.map(row => row.member.person_id)).toEqual([1])
    await wrapper.setData({ filters: { hideAlumns: false } })
    expect(wrapper.vm.filteredMembers.map(row => row.member.person_id)).toEqual([1, 2])
    wrapper.unmount()
  })

  it('renders a historical-only list and keeps keyword, role and sorting behavior', async () => {
    const { wrapper } = await render([], comparison([
      row(2, 'removed', { before: snapshot(2, { last_name: 'Zebra' }) }),
      row(3, 'removed', { before: snapshot(3, { last_name: 'Alpha' }) }),
    ]))
    expect(wrapper.text()).not.toContain('does not yet have any members')
    expect(wrapper.findAll('tbody')[0].text()).toContain('Alpha')
    await wrapper.setData({ filters: { keyword: 'Zebra', roleId: 102 } })
    await flushPromises()
    expect(wrapper.vm.filteredMembers).toHaveLength(1)
    await wrapper.setData({ filters: { roleId: 101 } })
    await flushPromises()
    expect(wrapper.vm.filteredMembers).toHaveLength(0)
    wrapper.unmount()
  })

  it('shows captured historical details without live navigation or compliance assumptions', () => {
    const wrapper = mount(MemberPreview, { props: { group: {}, snapshotOnly: true, member: snapshot(2, { credentials: ['PhD'], email: '<script>text</script>' }) },
      global: { config: { warnHandler: () => {} } } })
    expect(wrapper.text()).toContain('PhD')
    expect(wrapper.text()).toContain('<script>text</script>')
    expect(wrapper.find('script').exists()).toBe(false)
    expect(wrapper.text()).not.toMatch(/View profile|Attestation Required|Not completed/)
    expect(wrapper.find('img').exists()).toBe(false)
    wrapper.unmount()
  })
})

describe('member display adapter', () => {
  it('keeps person identities separate and appends captured after-members without mutating inputs', () => {
    const live = liveMember(1)
    const source = comparison([row(1, 'unchanged'), row(2, 'added')])
    const { rows, canUseLiveMember } = useScopeOfWorkMemberRows(() => [live], () => source, () => 'g')
    expect(rows.value.map(row => row.uuid)).toEqual(['g:person:1', 'g:person:2'])
    expect(rows.value[0].member).toBe(live)
    expect(rows.value[1].member).not.toBe(source.rows.members[1].after)
    expect(canUseLiveMember(rows.value[1])).toBe(false)
    rows.value[0].showDetails = true
    expect(live.showDetails).toBeUndefined()
  })
})

describe('member form persisted events', () => {
  function context() {
    const ctx = { saving: false, persistedChange: false, newMember: liveMember(), group: { uuid: 'g' },
      $emit: vi.fn(), $store: { dispatch: vi.fn().mockResolvedValue(), commit: vi.fn() },
      clearForm: vi.fn(), $router: { replace: vi.fn() }, addAnother: false }
    ctx.save = vi.fn(async () => { ctx.persistedChange = true; return { id: 11, person: { id: 1 } } })
    return ctx
  }
  it('emits saved once for normal save and updated once for save/edit profile', async () => {
    const ctx = context()
    await MemberForm.methods.saveAndExit.call(ctx)
    expect(ctx.$emit.mock.calls).toEqual([['saved']])
    ctx.$emit.mockClear()
    await MemberForm.methods.saveAndEditProfile.call(ctx)
    expect(ctx.$emit.mock.calls).toEqual([['updated']])
    expect(ctx.showProfileForm).toBe(true)
  })
  it('refreshes after partial persistence even when a later write fails', async () => {
    const ctx = context()
    ctx.save = async () => { ctx.persistedChange = true; throw new Error('role save failed') }
    await expect(MemberForm.methods.saveAndExit.call(ctx)).rejects.toThrow('role save failed')
    expect(ctx.$emit.mock.calls).toEqual([['updated']])
    expect(ctx.saving).toBe(false)
  })
  it('records a persisted invitation before a subsequent permission failure', async () => {
    const ctx = context()
    const member = liveMember(1, { permissions: [{ id: 1 }] })
    ctx.$store.dispatch.mockResolvedValueOnce({ data: { id: 11 } }).mockRejectedValueOnce(new Error('permission failure'))
    ctx.save = () => MemberForm.methods.inviteNewMember.call(ctx, ctx.group, member)
    await expect(MemberForm.methods.saveAndEditProfile.call(ctx)).rejects.toThrow('permission failure')
    expect(ctx.$emit.mock.calls).toEqual([['updated']])
  })
  it('waits for remaining role writes and reloads members before reporting partial failure', async () => {
    let finishRemoval
    const remaining = new Promise(resolve => { finishRemoval = resolve })
    const dispatch = vi.fn(name => {
      if (name === 'memberAssignRole') return Promise.reject(new Error('assignment failure'))
      if (name === 'memberRemoveRole') return remaining
      return Promise.resolve([])
    })
    const request = groupActions.memberSyncRoles({ dispatch }, { group: { uuid: 'g' },
      member: { id: 11, addedRoles: [coordinator], removedRoles: [chair] } })
    const assertion = expect(request).rejects.toThrow('assignment failure')
    await flushPromises()
    expect(dispatch).not.toHaveBeenCalledWith('getMembers', expect.anything())
    finishRemoval()
    await assertion
    expect(dispatch).toHaveBeenLastCalledWith('getMembers', { group: { uuid: 'g' }, force: true })
  })
  it('forces member reload and emits updated after embedded profile save, including reload failure', async () => {
    const ctx = context()
    await MemberForm.methods.handleProfileUpdate.call(ctx, { id: 1 })
    expect(ctx.$store.dispatch).toHaveBeenCalledWith('groups/getMembers', { group: ctx.group, force: true })
    expect(ctx.$emit.mock.calls).toEqual([['updated']])
    ctx.$emit.mockClear()
    ctx.$store.dispatch.mockRejectedValue(new Error('reload failed'))
    await expect(MemberForm.methods.handleProfileUpdate.call(ctx, { id: 1 })).rejects.toThrow('reload failed')
    expect(ctx.$emit.mock.calls).toEqual([['updated']])
  })
})
