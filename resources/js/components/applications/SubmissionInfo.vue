<script setup>
    import {computed} from 'vue'
    import {formatDate} from '@/date_utils.js'
    import {judgementColor} from '@/composables/judgement_utils.js'
    import { featureIsEnabled } from '@/utils.js';
import MarkdownBlock from '../MarkdownBlock.vue';

    const props = defineProps({
        group: {
            type: Object,
            required: true
        },
        step: {
            type: Number,
            required: true
        }
    })

    const submissions = computed(() => {
        return props.group.expert_panel.submissionsForStep(props.step);
    })

    const stepHasBeenSubmitted = computed(() => {
        return submissions.value && submissions.value.length > 0;
    });

    // const firstSubmission = computed(() => {
    //     if (!submissions.value) return null

    //     return submissions.value[0];
    // })

    const latestSubmission = computed(() => {
        return props.group.expert_panel.latestPendingSubmissionForStep(props.step);
    });

    const latestJudgements = computed(() => {
        if (!submissions.value)  return [];

        if (
            Number.parseInt(submissions.value.length) === 1
            || Number.parseInt(latestSubmission.value.submission_status_id) === 3
            || Number.parseInt(latestSubmission.value.submission_status_id) === 2
        ) {
            return latestSubmission.value.judgements;
        }

        return submissions.value[submissions.value.length-2].judgements;
    });


</script>

<template>
  <div v-if="step == group.expert_panel.current_step">
    <static-alert
      v-if="stepHasBeenSubmitted"
      class="mb-4 border-blue-700"
      variant="bland"
    >
      <div v-if="!latestSubmission.sent_to_chairs_at">
        <strong>Submitted</strong> by {{ latestSubmission.submitter ? latestSubmission.submitter.name : '' }} on
        <strong>{{ formatDate(latestSubmission.created_at) }}</strong>
        <MarkdownBlock v-if="latestSubmission.notes" class="ml-4" :markdown="latestSubmission.notes" />
      </div>
      <div v-if="latestSubmission.submission_status_id == 3">
        <strong>Sent to chairs</strong> on <strong>{{ formatDate(latestSubmission.sent_to_chairs_at) }}</strong>.
      </div>
      <div v-if="latestSubmission.submission_status_id == 2">
        <strong>{{ formatDate(latestSubmission.updated_at) }}</strong> - Revisions Requested.
      </div>
      <div v-if="featureIsEnabled('chair_review') && (latestSubmission.submission_status_id == 3)">
        <hr class="my-1">
        <strong>Latest Chair Decisions:</strong>
        <ul v-if="latestJudgements && latestJudgements.length > 0" class="list-disc pl-6">
          <li v-for="judgement in latestJudgements" :key="judgement.id">
            <strong>{{ judgement.person.name }}:</strong>
                        &nbsp;
            <badge :color="judgementColor(judgement)">
              {{ judgement.decision }}
            </badge>
            <div v-if="judgement.notes" class="text-sm">
              <strong>Notes:</strong> {{ judgement.notes }}
            </div>
          </li>
        </ul>
        <div v-else class="ml-4 text-gray-500">
          Awaiting decisions...
        </div>
      </div>
    </static-alert>
  </div>
</template>

<style scoped>
    .submission-log-table {
        @apply text-sm;
    }
    .submission-log-table td,
    .submission-log-table th {
        @apply border-0 border-b py-2;
    }
</style>
