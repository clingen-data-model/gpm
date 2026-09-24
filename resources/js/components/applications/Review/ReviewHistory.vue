<script setup>
import { inject, ref, watch } from 'vue'
import { api } from '@/http'
import ReviewRoundDetail from './ReviewRoundDetail.vue'

const historyState = inject('applicationReviewHistory', { history: ref(null), loading: ref(false), error: ref('') })
const history = historyState.history
const details = ref({})
const errors = ref({})
const busy = ref({})
let generation = 0
watch(history, () => { generation++; details.value = {}; errors.value = {}; busy.value = {} })
const date = value => value ? new Date(value).toLocaleString() : 'Unavailable'
async function loadDetail(round) {
  const id = round.submission_id
  if (busy.value[id]) return
  if (details.value[id]) { delete details.value[id]; return }
  const current = generation
  busy.value[id] = true
  delete errors.value[id]
  try {
    const response = await api.get(`/api/groups/${history.value.group_uuid}/application/review-history/${id}`)
    if (current === generation) details.value[id] = response.data
  } catch {
    if (current === generation) errors.value[id] = 'Unable to load historical detail. Please retry.'
  } finally {
    if (current === generation) busy.value[id] = false
  }
}
</script>

<template>
  <details class="screen-block" data-testid="review-history">
    <summary class="cursor-pointer font-semibold">Review History</summary>
    <p v-if="historyState.loading.value">Loading Review History…</p>
    <p v-else-if="historyState.error.value" role="alert">{{ historyState.error.value }}</p>
    <template v-else-if="history">
      <p v-if="history.initial_scope_of_work_version">
        Scope of Work Version {{ history.initial_scope_of_work_version.label }} — {{ history.initial_scope_of_work_version.status }}.
        <template v-if="history.initial_version_relationship !== 'explicit'">Its relationship to an initial Step 1 Review Round is not recorded.</template>
      </p>
      <p v-if="history.initial_step_1_approval?.approved_at">Recorded Step 1 approval: {{ date(history.initial_step_1_approval.approved_at) }}.</p>
      <p v-if="!history.cycles.length">No identifiable Step 1 Review Rounds are recorded.</p>
      <details v-for="cycle in history.cycles" :key="cycle.key" class="my-3 rounded border p-3">
        <summary class="cursor-pointer font-semibold">{{ cycle.title }} — {{ cycle.rounds.length }} Review {{ cycle.rounds.length === 1 ? 'Round' : 'Rounds' }}</summary>
        <p v-if="cycle.base_version">Based on Scope of Work Version {{ cycle.base_version.label }}</p>
        <p v-if="cycle.scope_of_work_version?.approved_at">Approved {{ date(cycle.scope_of_work_version.approved_at) }}</p>
        <p v-if="cycle.approval.relationship === 'unavailable'">Approval relationship unavailable.</p>
        <article v-for="round in [...cycle.rounds].reverse()" :key="round.submission_id" class="my-3 border-t pt-3">
          <h4>Review Round {{ round.review_round }} — {{ round.status }}</h4>
          <strong v-if="round.is_current_review">Current review</strong>
          <p>Submitted {{ date(round.submitted_at) }} by {{ round.submitted_by?.name ?? 'Unavailable' }}</p>
          <p v-if="round.closed_at">Outcome recorded {{ date(round.closed_at) }}</p>
          <p v-if="cycle.approval.relationship === 'explicit' && cycle.approval.submission_id === round.submission_id">Approved → Scope of Work Version {{ cycle.scope_of_work_version.label }}</p>
          <p v-if="round.reviewed_by">Approved by {{ round.reviewed_by.name }}</p>
          <p v-if="round.submitter_notes" class="whitespace-pre-wrap">Submitter notes: {{ round.submitter_notes }}</p>
          <p v-if="round.revisions_requested_notes" class="whitespace-pre-wrap">Revisions-requested notes: {{ round.revisions_requested_notes }}</p>
          <p v-for="judgement in round.reviewer_judgements" :key="judgement.id" class="whitespace-pre-wrap">
            Chair judgement — {{ judgement.reviewer?.name ?? 'Unavailable' }}: {{ judgement.decision }}. {{ judgement.notes }}
          </p>
          <p>Historical snapshot: {{ round.snapshot.availability }}</p>
          <p v-if="round.detail.availability !== 'available'">Submitted application details are unavailable for this historical Review Round.</p>
          <button v-else type="button" class="btn btn-xs" :disabled="busy[round.submission_id]" :aria-expanded="!!details[round.submission_id]" @click="loadDetail(round)">
            {{ details[round.submission_id] ? 'Hide detail' : round.detail.mode === 'submitted_state' ? 'View submitted application' : 'View changes' }}
          </button>
          <p v-if="errors[round.submission_id]" role="alert">{{ errors[round.submission_id] }}</p>
          <ReviewRoundDetail v-if="details[round.submission_id]" :detail="details[round.submission_id]" />
        </article>
      </details>
      <p v-for="item in history.ambiguous_submissions" :key="item.submission_id">Submission {{ item.submission_id }}: {{ item.reason }}</p>
    </template>
  </details>
</template>
