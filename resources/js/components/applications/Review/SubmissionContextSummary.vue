<script setup>
import { computed, inject } from 'vue';

const latestSubmission = inject('latestSubmission');

const submission = computed(() => latestSubmission?.value || {});
const data = computed(() => submission.value?.data || {});

const isScopeOfWorkRevision = computed(() => data.value.context === 'scope_of_work_revision');
const isApplicationSubmission = computed(() => data.value.context === 'application_submission');

const changes = computed(() => data.value.changes || []);
</script>

<template>
  <static-alert v-if="submission?.id" variant="info" class="screen-block">
    <div class="font-semibold">
      Submission Context
    </div>

    <div v-if="isScopeOfWorkRevision" class="mt-2">
      <p>
        This submission is for a Scope of Work revision
        <template v-if="data.target_version">
          to version <strong>{{ data.target_version }}</strong>
        </template>.
      </p>

      <p v-if="submission.notes" class="mt-2">
        <strong>Submitter notes:</strong> {{ submission.notes }}
      </p>

      <div v-if="changes.length" class="mt-3">
        <strong>Submitted changes:</strong>
        <ul class="mt-1 list-inside list-disc">
          <li v-for="change in changes" :key="change.id || `${change.label}-${change.entity_label}`">
            {{ change.label }}
            <template v-if="change.entity_label">— {{ change.entity_label }}</template>
          </li>
        </ul>
      </div>
    </div>

    <div v-else-if="isApplicationSubmission" class="mt-2">
      <p>
        This submission is tied to application snapshot
        <strong>version {{ data.application_snapshot_version || submission.application_snapshot?.version }}</strong>.
      </p>

      <p v-if="submission.notes" class="mt-2">
        <strong>Submitter notes:</strong> {{ submission.notes }}
      </p>
    </div>

    <div v-else class="mt-2">
      <p v-if="submission.notes">
        <strong>Submitter notes:</strong> {{ submission.notes }}
      </p>
    </div>
  </static-alert>
</template>