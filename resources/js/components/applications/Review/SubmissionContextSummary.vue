<script setup>
import { computed, inject, ref } from 'vue';
import { useStore } from 'vuex';
import { hasPermission } from '@/auth_utils.js';

const emit = defineEmits(['saved']);

const store = useStore();
const latestSubmission = inject('latestSubmission');
const group = inject('group');
const showApproveModal = ref(false);
const showRequestRevisionsModal = ref(false);
const revisionNotes = ref('');
const reviewErrors = ref({});
const submittingReviewAction = ref(false);

const submission = computed(() => latestSubmission?.value || {});
const data = computed(() => submission.value?.data || {});

const isScopeOfWorkRevision = computed(() => data.value.context === 'scope_of_work_revision');
const isApplicationSubmission = computed(() => data.value.context === 'application_submission');

const changes = computed(() => data.value.changes || []);

const scopeOfWorkRevisionUuid = computed(() => {
  return data.value.scope_of_work_version_uuid || null;
});

const canReviewScopeOfWorkRevision = computed(() => {
  return isScopeOfWorkRevision.value
    && scopeOfWorkRevisionUuid.value
    && (
      hasPermission('ep-applications-manage')
      || hasPermission('ep-applications-approve')
    );
});

const approveScopeOfWorkRevision = async () => {
  if (submittingReviewAction.value) { return; }
  submittingReviewAction.value = true;
  try {
    await store.dispatch('groups/approveScopeOfWorkRevision', { groupUuid: group.value.uuid, revisionUuid: scopeOfWorkRevisionUuid.value });
    store.commit('pushSuccess', 'Scope of Work revision approved.');
    showApproveModal.value = false;
    emit('saved');
  } finally {
    submittingReviewAction.value = false;
  }
};

const openRequestRevisionsModal = () => {
  revisionNotes.value = '';
  reviewErrors.value = {};
  showRequestRevisionsModal.value = true;
};

const requestScopeOfWorkRevisionChanges = async () => {
  reviewErrors.value = {};
  if (!revisionNotes.value.trim()) {
    reviewErrors.value = {
      notes: ['Revision request notes are required.'],
    };
    return;
  }

  if (submittingReviewAction.value) { return;}
  submittingReviewAction.value = true;
  try {
    await store.dispatch('groups/requestScopeOfWorkRevisionChanges', { groupUuid: group.value.uuid, submissionId: submission.value.id, notes: revisionNotes.value });
    store.commit('pushSuccess', 'Scope of Work revisions requested.');
    showRequestRevisionsModal.value = false;
    emit('saved');
  } finally {
    submittingReviewAction.value = false;
  }
};
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

      <div v-if="canReviewScopeOfWorkRevision" class="mt-4 flex gap-2">
        <button type="button" class="btn btn-sm blue" @click="showApproveModal = true">Approve Scope of Work revision</button>
        <button type="button" class="btn btn-sm" @click="openRequestRevisionsModal">Request revisions</button>
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
  <teleport to="body">
    <modal-dialog v-model="showApproveModal" title="Approve Scope of Work revision">
      <p>You are about to approve Scope of Work revision <strong>{{ data.target_version }}</strong>.</p>
      <p class="mt-2">Once approved, this revision will become the group’s current approved Scope of Work version.</p>
      <template #footer>
        <div class="flex justify-end gap-2">
          <button type="button" class="btn" :disabled="submittingReviewAction" @click="showApproveModal = false">Cancel</button>
          <button type="button" class="btn blue" :disabled="submittingReviewAction" @click="approveScopeOfWorkRevision">{{ submittingReviewAction ? 'Approving...' : 'Approve Revision' }}</button>
        </div>
      </template>
    </modal-dialog>

    <modal-dialog v-model="showRequestRevisionsModal" title="Request Scope of Work revisions">
      <p>Explain what changes are required before this Scope of Work revision can be approved.</p>
      <div class="mt-4">
        <input-row label="Required notes for the Expert Panel" :errors="reviewErrors.notes" vertical>
          <textarea v-model="revisionNotes" rows="5" class="w-full" :disabled="submittingReviewAction" />
        </input-row>
      </div>

      <template #footer>
        <div class="flex justify-end gap-2">
          <button type="button" class="btn" :disabled="submittingReviewAction" @click="showRequestRevisionsModal = false">Cancel</button>
          <button type="button" class="btn blue" :disabled="submittingReviewAction" @click="requestScopeOfWorkRevisionChanges">{{ submittingReviewAction ? 'Submitting...' : 'Request Revisions' }}</button>
        </div>
      </template>
    </modal-dialog>
  </teleport>
</template>