<template>
	<div v-if="status" class="mb-4 rounded-md border p-4 text-sm" :class="status.has_active_revision ? 'border-yellow-300 bg-yellow-50' : 'border-blue-200 bg-blue-50'">
		<div v-if="!status.has_approved_version">
			<div class="font-semibold text-yellow-900">Scope of Work versioning has not been initialized for this group.</div>
		</div>

		<div v-else-if="!status.has_active_revision">
			<div class="font-semibold text-blue-900">This application is approved.</div>
			<div class="mt-1 text-blue-800">
				Current Scope of Work version:
				<span class="font-semibold">{{ status.approved_version.version_label }}</span>.
				Future Scope of Work changes will be tracked and versioned.
			</div>
		</div>

		<div v-else>
			<h3 v-if="status.active_revision.status === 'draft'" class="font-semibold text-yellow-900">Draft Scope of Work changes detected.</h3>
			<h3 v-else-if="status.active_revision.status === 'submitted'" class="font-semibold text-yellow-900">Scope of Work revision submitted for review.</h3>
			<h3 v-else-if="status.active_revision.status === 'revisions_requested'" class="font-semibold text-yellow-900">Revisions requested for this Scope of Work update.</h3>		

			<div
				v-if="status.active_revision.status === 'revisions_requested' && status.active_revision.submission?.response_content"
				class="mt-2 rounded border border-yellow-300 bg-yellow-50 p-3 text-yellow-900"
				>
				<div class="font-semibold">Revision request notes:</div>
				<div class="mt-1 whitespace-pre-line">
					{{ status.active_revision.submission.response_content }}
				</div>
			</div>

			<div class="mt-3 text-yellow-800">
				Version:
				<span class="font-semibold">{{ status.active_revision.version_label }}</span>
				<template v-if="status.active_revision.base_version">
					based on version
					<span class="font-semibold"> {{ status.active_revision.base_version.version_label }} </span>
				</template>
			</div>

			<div class="mt-2 text-yellow-800">
				{{ status.active_revision.summary.total_changes }} change(s) detected.
				<template v-if="status.active_revision.summary.requires_submission">Some changes require approval.</template>
				<template v-else>These changes can be finalized without approval.</template>
			</div>

			<ul class="mt-2 list-inside list-disc text-yellow-900">
				<li v-for="change in status.active_revision.changes" :key="change.id">
					{{ scopeOfWorkChangeLabel(change) }}					
					<span v-if="change.requires_approval === 'yes'" class="font-semibold">— requires approval</span>
					<span v-else-if="change.requires_approval === 'conditional'" class="font-semibold">— may require approval</span>
					<button v-if="editable && change.can_discard && supportedChanges.includes(change.rule_key)"
						type="button" class="btn btn-xs ml-2 mb-1" :disabled="mutationBusy"
						@click="emit('discard-change', { revision: activeRevision, changeId: change.id })">Discard</button>
				</li>
			</ul>

			<div class="mt-3 flex flex-wrap gap-2">
				<button v-if="editable && status.active_revision.summary.can_finalize_without_approval" type="button" class="btn btn-xs" :disabled="mutationBusy" @click="$emit('finalize', status.active_revision)">
					Finalize as version {{ status.active_revision.version_label }}
				</button>
				<button v-if="editable && status.active_revision.summary.requires_submission" type="button" class="btn btn-xs" :disabled="mutationBusy" @click="showSubmitRevisionModal = true">
					{{ status.active_revision.status === 'revisions_requested' ? 'Resubmit for approval' : 'Submit for approval' }}
        		</button>				
				<button v-if="editable" type="button" class="btn btn-xs" :disabled="mutationBusy" @click="$emit('discard', status.active_revision)">Discard all changes</button>
			</div>
		</div>
	</div>
  <SubmissionConfirmationModal
    v-if="activeRevision"
    v-model="showSubmitRevisionModal"
    title="Submit Scope of Work revision"
    :submission-name="`Scope of Work revision ${activeRevision.version_label}`"
    notes-label="Required notes for reviewers:"
    submit-text="Submit for Approval"
    :submitting="mutationBusy"
    @submitted="submitRevision"
  />
</template>

<script setup>
import { scopeOfWorkChangeLabel } from '@/scope_of_work_change_label';
import { computed, ref } from 'vue';
import SubmissionConfirmationModal from '@/components/applications/SubmissionConfirmationModal.vue';

const props = defineProps({
  discarding: { type: Boolean, default: false },
  status: {
    type: Object,
    required: false,
    default: null,
  },
  canManage: {
    type: Boolean,
    required: false,
    default: false,
  },
});

const emit = defineEmits(['finalize', 'submit', 'approve', 'request-revisions', 'discard', 'discard-change']);
const showSubmitRevisionModal = ref(false);
const submittingRevision = ref(false);
const activeRevision = computed(() => props.status?.active_revision || null);
const supportedChanges = ['panel_name.rename', 'scope_description.update'];
const editable = computed(() => ['draft', 'revisions_requested'].includes(activeRevision.value?.status));
const mutationBusy = computed(() => props.discarding || submittingRevision.value);
const submitRevision = (notes) => {
  if (!activeRevision.value || mutationBusy.value) { return; }
  submittingRevision.value = true;
  emit('submit', { revision: activeRevision.value, notes, done: () => {
    submittingRevision.value = false;
    showSubmitRevisionModal.value = false;
	}});
};
</script>
