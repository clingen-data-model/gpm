<script setup>
import {computed} from 'vue'
import {useStore} from 'vuex';
import StepApproveControl from './StepApproveControl.vue'
import StepRequestRevisionsControl from './StepRequestRevisionsControl.vue'
import StepSendToChairsControl from './StepSendToChairsControl.vue'
import SubmissionInfo from './SubmissionInfo.vue';

const props = defineProps({
    step: {
        type: Number,
        required: true
    },
    approveLabel: {
        type: String,
        required: false,
        default: 'Approve'
    }
});

const emit = defineEmits([
  'updated',
  'stepApproved',
  'sentToChairs',
  'revisionsRequested',
])

const store = useStore()

const group = computed(() => store.getters['groups/currentItemOrNew'])

const showSendToChairsControl = computed(() => {
    if (!group.value) {
        return false;
    }
    const latestSubmission = group.value.expert_panel.latestPendingSubmissionForStep(props.step);
    return latestSubmission
        && latestSubmission.submission_status_id === 1;
})

</script>

<template>
  <div class="border-t border-b py-4 mb-6">
    <SubmissionInfo class="mb-4" :group="group" :step="step" />
    <div class="flex w-full space-x-4">
      <StepApproveControl
        class="flex-1"
        :group="group"
        :step="step"
        @step-approved="() => {emit('stepApproved'); emit('updated')}"
      >
        {{ approveLabel }}
      </StepApproveControl>

      <StepSendToChairsControl
        v-if="showSendToChairsControl"
        class="flex-1"
        :group="group"
        @sent-to-chairs="() => {emit('sentToChairs'); emit('updated'); }"
      />

      <StepRequestRevisionsControl
        v-if="group.expert_panel.hasPendingSubmissionForCurrentStep"
        class="flex-1"
        :group="group"
        @revisions-requested="() => {emit('revisionsRequested'); emit('updated')}"
      />
    </div>
  </div>
</template>
