<script setup>
import {computed} from 'vue'
import {useStore} from 'vuex';
import SubmissionInfo from './SubmissionInfo.vue';
import StepApproveControl from './StepApproveControl.vue'
import StepSendToChairsControl from './StepSendToChairsControl.vue'
import StepRequestRevisionsControl from './StepRequestRevisionsControl.vue'

const store = useStore()

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
        <SubmissionInfo class="mb-4" :group="group" :step="step"></SubmissionInfo>
        <div class="flex w-full space-x-4">
            <StepApproveControl
                class="flex-1"
                :group="group"
                :step="step"
                @stepApproved="() => {$emit('stepApproved'); $emit('updated')}"
            >
                {{ approveLabel }}
            </StepApproveControl>

            <StepSendToChairsControl
                v-if="showSendToChairsControl"
                class="flex-1"
                :group="group"
                @sentToChairs="() => {$emit('sentToChairs'); $emit('updated'); }"
            />

            <StepRequestRevisionsControl
                v-if="group.expert_panel.hasPendingSubmissionForCurrentStep"
                class="flex-1"
                :group="group"
                @revisionsRequested="() => {$emit('revisionsRequested'); $emit('updated')}"
            />
        </div>
    </div>
</template>
