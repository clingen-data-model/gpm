<script setup>
    import {formatDate} from '@/date_utils.js'

    const props = defineProps({
        group: {
            type: Object,
            required: true
        }
    })
</script>

<template>
    <static-alert 
        variant="info" 
        v-if="group.expert_panel.hasPendingSubmissionForCurrentStep"
        class="mb-4"
    >
        This step was submitted by <strong>{{group.expert_panel.pendingSubmission.submitter.name}}</strong> on 
        <strong>{{formatDate(group.expert_panel.pendingSubmission.created_at)}}</strong> 
        <span v-if="group.expert_panel.pendingSubmission.notes">
            with the following notes:
        </span>
        <blockquote v-if="group.expert_panel.pendingSubmission.notes">
            {{group.expert_panel.pendingSubmission.notes}}
        </blockquote>
    </static-alert>
</template>