import { describe, it, expect, vi } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createStore } from 'vuex'
import { createRouter, createMemoryHistory } from 'vue-router'
import Application from '@/domain/application'
import ApplicationsTable from './ApplicationsTable.vue'
import ApplicationActivity from '@/components/dashboard/ApplicationActivity.vue'
import configs from '@/configs'
import { formatDate } from '@/date_utils'
import { api } from '@/http'

vi.mock('@/http', () => ({ api: { get: vi.fn() } }))
vi.mock('@/auth_utils', () => ({ hasPermission: () => false }))
vi.mock('@/utils.js', () => ({ featureIsEnabled: () => false }))
const DataTable = { props: ['data', 'fields'], template: '<div />' }
async function render(Component, application, props = {}) {
  const store = createStore({ modules: { applications: { namespaced: true,
    getters: { all: () => [application] }, actions: { getApplications: vi.fn() },
  } } })
  const router = createRouter({ history: createMemoryHistory(), routes: [{ path: '/', component: { template: '<div />' } }] })
  await router.push('/')
  const wrapper = mount(Component, { props, global: { plugins: [store, router], stubs: { 'data-table': DataTable, popper: true, badge: true, 'icon-checkmark': true } } })
  await flushPromises()
  return wrapper
}
const submission = status => ({ id: 9, created_at: '2026-10-21T12:00:00Z', status: { name: status }, judgements: [] })
function application(active = null) {
  return new Application({ id: 1, current_step: 5, date_completed: '2025-01-01', deleted_at: null,
    group: { latest_submission: submission('Approved'), active_scope_of_work_submission: active }, next_actions: [] })
}
describe('application review list display', () => {
  it.each(['Pending', 'Revisions Requested', 'Under Chair Review'])('uses the active revision for %s', async status => {
    const active = submission(status)
    const app = application(active)
    const wrapper = await render(ApplicationsTable, app)
    const fields = wrapper.getComponent(DataTable).props('fields')
    expect(fields.find(f => f.name === 'current_step').resolveValue(app)).toBe('Scope of Work Revision')
    expect(fields.find(f => f.label === 'Submission Status').resolveValue(app)).toBe(status)
    expect(fields.find(f => f.label === 'Last Submitted').resolveValue(app)).toBe(formatDate(active.created_at))
    expect(app.current_step).toBe(5)
    expect(app.isCompleted).toBe(true)
    wrapper.unmount()
  })
  it('keeps Completed without an active revision and preserves incomplete step display', async () => {
    const app = application()
    const wrapper = await render(ApplicationsTable, app)
    const field = wrapper.getComponent(DataTable).props('fields').find(f => f.name === 'current_step')
    expect(field.resolveValue(app)).toBe('Completed')
    expect(field.resolveValue(new Application({ current_step: 1 }))).toBe(1)
    wrapper.unmount()
  })
  it('uses unfinished revision assignments for Next Actions and Waiting On', async () => {
    const app = application(submission('Revisions Requested'))
    const ep = configs.nextActions.assignees['expert-panel'].id
    const chairs = configs.nextActions.assignees.chairs.id
    app.next_actions = [
      { id: 1, assigned_to: chairs, date_completed: '2026-10-20', entry: 'Old review' },
      { id: 2, assigned_to: ep, date_completed: null, entry: 'Update Scope of Work revision and resubmit for approval.' },
    ]
    const wrapper = await render(ApplicationsTable, app)
    expect(app.pendingActionsByAssignee[ep].map(a => a.id)).toEqual([2])
    expect(app.pendingActionsByAssignee[chairs]).toEqual([])
    await wrapper.setData({ waitingOn: ep })
    expect(wrapper.getComponent(DataTable).props('data')).toHaveLength(1)
    await wrapper.setData({ waitingOn: chairs })
    expect(wrapper.getComponent(DataTable).props('data')).toHaveLength(0)
    wrapper.unmount()
  })
  it.each(['Definition', 'Sustained Curation'])('dashboard uses the submitted %s step label and active status', async step => {
    const row = { submitted_step: `Scope of Work Revision — ${step}`, submission: submission('Pending') }
    api.get.mockResolvedValue({ data: { data: [row] } })
    const wrapper = await render(ApplicationActivity, application(), { user: { person: { id: 1 } } })
    const table = wrapper.getComponent(DataTable)
    expect(table.props('fields').find(f => f.label === 'Submitted Step').name).toBe('submitted_step')
    expect(table.props('data')[0]).toEqual(row)
    wrapper.unmount()
  })
})
