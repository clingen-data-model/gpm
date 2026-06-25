<script setup>
import { computed } from 'vue'
import { useStore } from 'vuex'
import SubmissionInfo from './SubmissionInfo.vue'
import StepApproveControl from './StepApproveControl.vue'
import StepSendToChairsControl from './StepSendToChairsControl.vue'
import StepRequestRevisionsControl from './StepRequestRevisionsControl.vue'
import ScopeOfWorkApproveControl from './ScopeOfWorkApproveControl.vue'

const props = defineProps({
  step: {
    type: Number,
    required: true,
  },
  approveLabel: {
    type: String,
    required: false,
    default: 'Approve'
  }
})

const emit = defineEmits([
  'updated',
  'stepApproved',
  'sentToChairs',
  'revisionsRequested',
  'scopeOfWorkApproved'
])

const store = useStore();

const group = computed(() => store.getters['groups/currentItemOrNew'])
const expertPanel = computed(() => group.value?.expert_panel)

/* Find the newest active SoW submission regardless of the application's current_step.
    1 = Pending
    2 = Revisions Requested
    3 = Under Chair Review */

const activeScopeOfWorkSubmission = computed(() => {
  const submissions = expertPanel.value?.submissions ?? []

  return [...submissions].filter(submission => {
      const statusId = Number.parseInt(submission.submission_status_id)
      return submission.data?.context === 'scope_of_work_revision' && [1, 2, 3].includes(statusId)
    })
    .sort((a, b) => Number(b.id) - Number(a.id))[0] ?? null
})

const scopeOfWorkApprovalStep = computed(() => {
  return Number.parseInt(activeScopeOfWorkSubmission.value?.data?.approval_step ?? 1)
})

const isScopeOfWorkStep = computed(() => {
  return Boolean(activeScopeOfWorkSubmission.value) && Number(props.step) === scopeOfWorkApprovalStep.value
})

/*
 * If there is an active SoW revision, only return it for the
 * step identified by data.approval_step.
 *
 * Otherwise preserve the regular application lookup.
 */
const latestSubmission = computed(() => {
  if (activeScopeOfWorkSubmission.value) {
    return isScopeOfWorkStep.value ? activeScopeOfWorkSubmission.value : null
  }
  return expertPanel.value?.latestPendingSubmissionForStep(props.step) ?? null

})

const shouldShowForStep = computed(() => {
  if (!activeScopeOfWorkSubmission.value) {
    return true
  }
  return isScopeOfWorkStep.value
})

const isScopeOfWorkRevision = computed(() => {
  return latestSubmission.value?.data?.context === 'scope_of_work_revision'
})

const showSendToChairsControl = computed(() => {
  return Number.parseInt(latestSubmission.value?.submission_status_id) === 1
})

const showRequestRevisionsControl = computed(() => {
  const statusId = Number.parseInt(latestSubmission.value?.submission_status_id)
  return [1, 3].includes(statusId)
})

const showScopeOfWorkApproveControl = computed(() => {
  const statusId = Number.parseInt(latestSubmission.value?.submission_status_id)
  return isScopeOfWorkRevision.value && [1, 3].includes(statusId)
})
</script>

<template>
  <div v-if="shouldShowForStep" class="border-t border-b py-4 mb-6">
    <SubmissionInfo v-if="latestSubmission" class="mb-4" :submission="latestSubmission" />
    <div class="flex w-full space-x-4">
      <StepApproveControl
        v-if="!isScopeOfWorkRevision"
        class="flex-1"
        :group="group"
        :step="step"
        @step-approved="() => {
          emit('stepApproved')
          emit('updated')
        }"
      >
        {{ approveLabel }}
      </StepApproveControl>

      <ScopeOfWorkApproveControl
        v-if="showScopeOfWorkApproveControl"
        class="flex-1"
        :group="group"
        :submission="latestSubmission"
        @approved="() => {
          emit('scopeOfWorkApproved')
          emit('updated')
        }"
      />

      <StepSendToChairsControl
        v-if="showSendToChairsControl"
        class="flex-1"
        :group="group"
        :submission="latestSubmission"
        @sent-to-chairs="() => {
          emit('sentToChairs')
          emit('updated')
        }"
      />

      <StepRequestRevisionsControl
        v-if="showRequestRevisionsControl"
        class="flex-1"
        :group="group"
        :submission="latestSubmission"
        @revisions-requested="() => {
          emit('revisionsRequested')
          emit('updated')
        }"
      />
    </div>
  </div>
</template>
