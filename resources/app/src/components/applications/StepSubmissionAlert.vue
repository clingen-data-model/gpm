<script setup>
    import {computed} from 'vue'
    import {formatDate} from '@/date_utils.js'

    const props = defineProps({
        group: {
            type: Object,
            required: true
        }
    })

    const hasPendingSubmission = computed(() => props.group.expert_panel.hasPendingSubmissionForCurrentStep);
    const pendingSubmission = computed(() => props.group.expert_panel.pendingSubmission);
</script>

<template>
    <div>
        <static-alert 
            variant="info" 
            v-if="hasPendingSubmission"
            class="mb-4 space-y-4"
        >
            <ul class="list-disc pl-4">
                <li>
                    This step was submitted by <strong>{{group.expert_panel.pendingSubmission.submitter.name}}</strong> on 
                    <strong>{{formatDate(group.expert_panel.pendingSubmission.created_at)}}</strong> 
                    <span v-if="group.expert_panel.pendingSubmission.notes">
                        with the following notes:
                    </span>
                    <blockquote v-if="group.expert_panel.pendingSubmission.notes">
                        {{group.expert_panel.pendingSubmission.notes}}
                    </blockquote>
                </li>

                <li v-if="pendingSubmission.status.name == 'Under Chair Review'">
                    Sent to chairs for review on <strong>{{formatDate(pendingSubmission.updated_at)}}</strong>
                </li>
            </ul>
        </static-alert>
    </div>
</template>