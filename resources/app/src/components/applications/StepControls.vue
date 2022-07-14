<script setup>
import {computed} from 'vue'
import {useStore} from 'vuex';
import SubmissionAlert from './StepSubmissionAlert.vue';
import StepApproveControl from './StepApproveControl.vue'
import StepSendToChairsControl from './StepSendToChairsControl'
import StepRequestRevisionsControl from './StepRequestRevisionsControl'

const store = useStore()

defineProps({    
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

</script>

<template>
    <div class="border-t border-b py-4 mb-6">
        <submission-alert :group="group"></submission-alert>
        <div class="flex w-full space-x-4">
            <StepApproveControl
                class="flex-1"
                :group="group" 
                :step="step" 
                @stepApproved="() => {$emit('stepApproved'); $emit('updated')}"
            >
                {{approveLabel}}
            </StepApproveControl>

            <StepSendToChairsControl 
                class="flex-1"
                :group="group" 
                @sentToChairs="() => {$emit('sentToChairs'); $emit('updated'); }" 
            />

            <StepRequestRevisionsControl 
                class="flex-1"
                :group="group"
                @revisionsRequested="() => {$emit('revisionsRequested'); $emit('updated')}"
            />
        </div>
    </div>
</template>
