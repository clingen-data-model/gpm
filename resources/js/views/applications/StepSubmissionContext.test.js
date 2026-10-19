import { mount, flushPromises } from '@vue/test-utils'
import { reactive, ref } from 'vue'
import { createStore } from 'vuex'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import ApplicationDetail from './ApplicationDetail.vue'
import ChairApproverControls from '@/components/applications/Review/ChairApproverControls.vue'
import StepTabs from '@/components/applications/StepTabs.vue'
import StepControls from '@/components/applications/StepControls.vue'
import SubmissionContextSummary from '@/components/applications/Review/SubmissionContextSummary.vue'
import ApplicationReviewSummary from '@/components/applications/Review/ApplicationReviewSummary.vue'
import SubmissionInfo from '@/components/applications/SubmissionInfo.vue'
import TabsContainer from '@/components/TabsContainer.vue'
import TabItem from '@/components/TabItem.vue'
import ExpertPanel from '@/domain/expert_panel'
import { latestInitialSubmissionForStep, submissionReviewStep } from '@/domain/review_submission'
const api = vi.hoisted(() => ({ get: vi.fn() }))
vi.mock('@/http', () => ({ api }))
vi.mock('@/auth_utils.js', () => ({ hasPermission: () => true, hasAnyPermission: () => true, hasRole: () => false }))
vi.mock('@/composables/comment_manager.js', () => ({ default: () => ({ getComments: vi.fn(), getSummary: vi.fn(), dispose: vi.fn(), summary: [{ id: 10, body: 'Application-wide comment' }] }) }))
vi.mock('@/composables/scope_of_work_comparison', () => ({ useScopeOfWorkComparison: () => ({}) }))
vi.mock('./ApplicationReview.vue', () => ({ default: { template: '<div />' } }))
const empty = { template: '<div />' }
const submission = (id, step, round) => ({ id, submission_type_id: step === 1 ? 1 : 2,
  submission_status_id: 4, type: { name: step === 1 ? 'Definition' : 'Sustained Curation' },
  data: { context: 'application_submission' }, notes: `Step ${step} round ${round} note`,
  created_at: '2026-10-14T10:00:00Z', submitter: { name: `Step ${step} submitter` } })
async function setup(type = 2, fourthRounds = 0) {
  const submissions = [submission(1, 1, 1), submission(2, 1, 2), submission(3, 1, 3)]
  for (let round = 1; round <= fourthRounds; round++) submissions.push(submission(3 + round, 4, round))
  const group = reactive({ id: 1, uuid: 'panel', is_gcep: type === 1, hasContacts: true,
    expert_panel: { current_step: type === 1 ? 1 : 4, is_gcep: type === 1, expert_panel_type_id: type, submissions } })
  api.get.mockImplementation(url => Promise.resolve({ data: url.endsWith('latest-submission') ? submissions.at(-1) : {
    cycles: [1, 4].map(step => ({ step, rounds: submissions.filter(s => submissionReviewStep(s) === step)
      .map((s, index) => ({ submission_id: s.id, review_round: index + 1 })) })) } }))
  const store = createStore({ getters: { 'groups/currentItemOrNew': () => group } })
  vi.spyOn(store, 'dispatch').mockResolvedValue()
  const wrapper = mount(ApplicationDetail, { props: { uuid: 'panel' }, global: { plugins: [store],
    components: { TabsContainer, TabItem },
    mocks: { hasAnyPermission: () => true, $route: { meta: {}, query: {} }, $router: { replace: vi.fn(), push: vi.fn() } },
    stubs: { ScreenTemplate: { template: '<div><slot /></div>' }, 'static-alert': { template: '<div><slot /></div>' },
      BasicInfoData: empty, NextActions: empty, ProgressChart: empty, ApplicationLog: empty, ReviewHistory: empty,
      ScopeOfWorkNameComparison: empty, 'router-link': true, 'router-view': true, 'modal-dialog': true,
      StepOne: { components: { StepControls }, template: '<StepControls :step="1" />' },
      StepFour: { components: { StepControls }, template: '<StepControls :step="4" />' }, StepTwo: empty, StepThree: empty,
      StepApproveControl: empty, StepSendToChairsControl: empty, StepRequestRevisionsControl: empty, ScopeOfWorkApproveControl: empty,
      ApplicationReviewSummary: { props: ['summary'], template: '<div>Application Review Summary</div>' } },
  } })
  await flushPromises()
  return { wrapper, group }
}
beforeEach(() => vi.clearAllMocks())
describe.each([2, 3])('panel type %s submission context', type => {
  it('does not borrow Step 1 context before Step 4 submission, and switches back to Step 1 Round 3', async () => {
    const { wrapper } = await setup(type)
    expect(wrapper.findComponent(SubmissionContextSummary).text()).toBe('')
    expect(wrapper.findComponent(SubmissionInfo).exists()).toBe(false)
    expect(wrapper.text()).not.toContain('Step 1 round 3 note')
    const summary = wrapper.findComponent(ApplicationReviewSummary)
    expect(summary.exists()).toBe(true)
    expect(summary.props('summary')).toEqual([{ id: 10, body: 'Application-wide comment' }])
    await wrapper.findComponent(StepTabs).findAll('li.tab')[0].trigger('click')
    await flushPromises()
    expect(wrapper.findComponent(SubmissionContextSummary).text()).toContain('Step 1: Group Definition')
    expect(wrapper.findComponent(SubmissionContextSummary).text()).toContain('Review Round 3')
    expect(wrapper.findComponent(SubmissionContextSummary).text()).toContain('Step 1 round 3 note')
    expect(wrapper.findComponent(SubmissionInfo).text()).toContain('Step 1 round 3 note')
    expect(summary.props('summary')).toEqual([{ id: 10, body: 'Application-wide comment' }])
    wrapper.unmount()
  })
  it.each([1, 2])('uses Step 4 round %s notes in both places', async rounds => {
    const { wrapper } = await setup(type, rounds)
    const context = wrapper.findComponent(SubmissionContextSummary).text()
    expect(context).toContain('Step 4: Sustained Curation')
    expect(context).toContain(`Review Round ${rounds}`)
    expect(context).toContain(`Step 4 round ${rounds} note`)
    expect(wrapper.findComponent(SubmissionInfo).text()).toContain(`Step 4 round ${rounds} note`)
    expect(wrapper.text()).not.toContain('Step 1 round 3 note')
    await wrapper.findComponent(StepTabs).findAll('li.tab')[0].trigger('click')
    await flushPromises()
    expect(wrapper.findComponent(SubmissionContextSummary).text()).toContain('Step 1 round 3 note')
    wrapper.unmount()
  })
})
it('retains GCEP Step 1 context', async () => {
  const { wrapper } = await setup(1)
  expect(wrapper.findComponent(SubmissionContextSummary).text()).toContain('Step 1 round 3 note')
  expect(wrapper.findComponent(SubmissionContextSummary).text()).toContain('Review Round 3')
  wrapper.unmount()
})
it('resolves stored metadata without mutating submission type names or depending on array order', () => {
  const first = submission(3, 1, 3), fourth = submission(4, 4, 1)
  const panel = new ExpertPanel({ submissions: [fourth, first] })
  expect(panel.latestPendingSubmissionForStep(1).id).toBe(3)
  expect(panel.latestPendingSubmissionForStep(4).id).toBe(4)
  expect(panel.submissions.map(s => s.type.name)).toEqual(['Sustained Curation', 'Definition'])
  expect(latestInitialSubmissionForStep([first], 4)).toBeNull()
  expect(latestInitialSubmissionForStep([first], 2)).toBeNull()
  expect(submissionReviewStep({ data: { approval_step: 4 } })).toBe(4)
  expect(latestInitialSubmissionForStep([first, { ...fourth, data: { context: 'scope_of_work_revision', approval_step: 4 } }], 4)).toBeNull()
})

it('chair controls follow the resolved step without falling back to the global latest submission', async () => {
  const selected = ref(null)
  const wrapper = mount(ChairApproverControls, { global: {
    plugins: [createStore({ getters: { currentUser: () => ({ person: { id: 1 } }) } })],
    provide: { reviewSubmission: selected, latestSubmission: ref(submission(3, 1, 3)),
      commentManager: ref({ commentsForEp: [], openInternal: [] }) },
    stubs: { JudgementDetail: true, JudgementForm: true, 'modal-dialog': true, note: true },
  } })
  expect(wrapper.text()).not.toContain('Step 1 round 3 note')
  selected.value = submission(4, 4, 1)
  await flushPromises()
  expect(wrapper.text()).toContain('Step 4 round 1 note')
  expect(wrapper.text()).not.toContain('Step 1 round 3 note')
  wrapper.unmount()
})
