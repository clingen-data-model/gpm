<script setup>
import {computed} from 'vue'
import {formatDate} from '@/date_utils.js'
import {judgementColor} from '@/composables/judgement_utils.js'
import { featureIsEnabled } from '@/utils.js';
import MarkdownBlock from '../MarkdownBlock.vue';

const props = defineProps({
  submission: {
    type: Object,
    required: true,
  },
})
const latestSubmission = computed(() => props.submission)

const latestJudgements = computed(() => {
  return latestSubmission.value?.judgements ?? []
})

const isScopeOfWorkRevision = computed(() => {
  return latestSubmission.value?.data?.context === 'scope_of_work_revision'
})

const targetVersion = computed(() => {
  return latestSubmission.value?.data?.target_version ?? null
})


</script>

<template>
  <div v-if="latestSubmission">
    <static-alert
      class="mb-4 border-blue-700"
      variant="bland"
    >
      <div v-if="isScopeOfWorkRevision">
        <strong>Scope of Work Revision <span v-if="targetVersion">{{ targetVersion }}</span></strong>
      </div>
      <div v-if="!latestSubmission.sent_to_chairs_at">
        <strong>Submitted</strong> by {{ latestSubmission.submitter?.name ?? '' }} on
        <strong>{{ formatDate(latestSubmission.created_at) }}</strong>
        <MarkdownBlock v-if="latestSubmission.notes" class="ml-4" :markdown="latestSubmission.notes" />
      </div>
      <div v-if="Number(latestSubmission.submission_status_id) == 3">
        <strong>Sent to chairs</strong> on <strong>{{ formatDate(latestSubmission.sent_to_chairs_at) }}</strong>.
      </div>
      <div v-if="Number(latestSubmission.submission_status_id) == 2">
        <strong>{{ formatDate(latestSubmission.updated_at) }}</strong> - Revisions Requested.
      </div>
      <div v-if="featureIsEnabled('chair_review') && (Number(latestSubmission.submission_status_id) == 3)">
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
