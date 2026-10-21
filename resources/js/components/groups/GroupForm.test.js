import { flushPromises, mount } from '@vue/test-utils'
import { afterEach, describe, expect, it, vi } from 'vitest'
import configs from '@/configs'
import { hasRole } from '@/auth_utils'
import Group from '@/domain/group'
import GroupForm from './GroupForm.vue'
import GroupDetailHeader from '@/views/groups/GroupDetailHeader.vue'

vi.mock('@/http', () => ({
  api: { get: vi.fn().mockResolvedValue({ data: { data: [] } }), put: vi.fn() },
  isValidationError: vi.fn(() => false),
}))

vi.mock('@/auth_utils', () => ({
  hasRole: vi.fn(() => false),
}))

const group = () => ({
  id: 1,
  uuid: 'group-uuid',
  group_type_id: configs.groups.types.gcep.id,
  affiliation_id: '40123',
  name: 'Panel name',
  expert_panel: {
    long_base_name: 'Panel name',
    short_base_name: 'PN',
    isDirty: () => false,
  },
  isDirty: attribute => attribute === 'affiliation_id',
})

const mountForm = currentGroup => mount(GroupForm, {
  global: {
    mocks: {
      $store: {
        getters: {
          'groups/currentItem': currentGroup,
          'cdwgs/all': [],
        },
        dispatch: vi.fn().mockResolvedValue([]),
        commit: vi.fn(),
      },
    },
    mixins: [{ methods: { hasPermission: () => false } }],
    stubs: {
      'input-row': {
        props: ['label'],
        template: '<label class="input-row">{{ label }}<input></label>',
      },
      'dictionary-row': {
        props: ['label'],
        template: '<div class="dictionary-row">{{ label }} <slot /></div>',
      },
      note: true,
    },
  },
})

const mountGroupHeader = currentGroup => mount(GroupDetailHeader, {
  props: { group: currentGroup },
  global: {
    mixins: [{ methods: { hasRole } }],
    stubs: {
      ScopeOfWorkNameComparison: true,
      EmitCheckpointsButton: true,
      'group-breadcrumbs': true,
      badge: true,
      note: true,
      'router-link': true,
      'dictionary-row': { template: '<div><slot name="label" /><slot /></div>' },
    },
  },
})

afterEach(() => {
  vi.clearAllMocks()
})

describe('group form Affiliation ID access', () => {
  it('shows the Affiliation ID as read-only and does not submit it for a coordinator', async () => {
    const wrapper = mountForm(group())
    const saveAffiliationId = vi.spyOn(wrapper.vm, 'saveAffiliationId')
    await flushPromises()

    expect(wrapper.findAll('.dictionary-row').some(row => row.text().includes('Affiliation ID 40123'))).toBe(true)
    expect(wrapper.findAll('.input-row').some(row => row.text().includes('Affiliation ID'))).toBe(false)

    await wrapper.vm.saveGroupData()
    expect(saveAffiliationId).not.toHaveBeenCalled()
    wrapper.unmount()
  })

  it.each(['super-user', 'super-admin'])('keeps the editable input for %s', role => {
    hasRole.mockImplementation(candidate => candidate === role)
    const wrapper = mountForm(group())

    expect(wrapper.findAll('.input-row').some(row => row.text().includes('Affiliation ID'))).toBe(true)
    wrapper.unmount()
  })

  it('lets a coordinator open Edit Group Info', async () => {
    hasRole.mockImplementation(candidate => candidate === 'coordinator')
    const wrapper = mountGroupHeader(new Group({
      id: 1,
      name: 'Panel name',
      group_type_id: configs.groups.types.gcep.id,
      status: { name: 'Active' },
      expert_panel: { long_base_name: 'Panel name', short_base_name: 'PN' },
    }))
    const editButton = wrapper.findAll('button').find(button => button.text().trim() === 'Edit Group Info')

    expect(editButton).toBeTruthy()
    await editButton.trigger('click')
    expect(wrapper.emitted('showEdit')).toHaveLength(1)
    wrapper.unmount()
  })
})
