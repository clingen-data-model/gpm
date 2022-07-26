<script setup>
    import {computed, inject} from 'vue'
    import {formatDate} from '@/date_utils.js'
    import {judgementColor} from '@/composables/judgement_utils.js'

    const props = defineProps({
        group: {
            type: Object,
            required: true
        }
    })

    const latestSubmission = inject('latestSubmission')

    const hasPendingSubmission = computed(() => props.group.expert_panel.hasPendingSubmissionForCurrentStep);
    const pendingSubmission = computed(() => props.group.expert_panel.pendingSubmission);

    const judgements = computed(() => {
        if (!latestSubmission || !latestSubmission.judgements) {
            return []
        }
        return latestSubmission.judgements
    });
</script>

<template>
    <div>
        <static-alert 
            variant="bland" 
            v-if="hasPendingSubmission"
            class="mb-4 border-blue-700"
        >
            <h3 class="border-b pb-1 mb-1">Submission Info:</h3>
            <ul class="list-disc pl-4 flex flex-col space-y-1">
                <li>
                    <strong>Submitted</strong> by <strong>{{group.expert_panel.pendingSubmission.submitter.name}}</strong> on 
                    <strong>{{formatDate(group.expert_panel.pendingSubmission.created_at)}}</strong> 
                    <span v-if="group.expert_panel.pendingSubmission.notes">
                        with the following notes:
                    </span>
                    <blockquote v-if="group.expert_panel.pendingSubmission.notes">
                        {{group.expert_panel.pendingSubmission.notes}}
                    </blockquote>
                </li>

                <li v-if="pendingSubmission.status.name == 'Under Chair Review'">
                   <strong>Sent to chairs</strong> for review on <strong>{{formatDate(pendingSubmission.updated_at)}}</strong>
                    <ul v-if="latestSubmission.judgements.length > 0"  class="list-disc pl-6 flex flex-col space-y-0.5">
                        <li v-for="j in latestSubmission.judgements" :key="j.id">
                            <strong>{{j.person.name}}:</strong> <badge :color="judgementColor(j)">{{j.decision}}</badge>
                            on <strong>{{formatDate(j.updated_at)}}</strong>
                            <p class="text-sm" v-if="j.notes"><strong>Notes for EP:</strong> {{j.notes}}</p>
                        </li>
                    </ul>
                </li>
            </ul>
        </static-alert>
    </div>
</template>