<template>
    <div v-if="status" class="mb-4 rounded-md border border-blue-200 bg-blue-50 p-4 text-sm">
        <div v-if="!status.has_approved_version">
            <div class="font-semibold text-blue-900">
                Scope of Work versioning has not been initialized for this group.
            </div>
        </div>

        <div v-else-if="!status.has_active_revision">
            <div class="font-semibold text-blue-900">
                This application is approved.
            </div>
            <div class="mt-1 text-blue-800">
                Current Scope of Work version:
                <span class="font-semibold">{{ status.approved_version.version_label }}</span>.
                Future Scope of Work changes will be tracked and versioned.
            </div>
        </div>

        <div v-else>
            <div
                v-if="status.active_revision.status === 'draft'"
                class="font-semibold text-blue-900"
            >
                Draft Scope of Work changes detected.
            </div>

            <div
                v-else-if="status.active_revision.status === 'submitted'"
                class="font-semibold text-blue-900"
            >
                Scope of Work revision submitted for review.
            </div>

            <div
                v-else-if="status.active_revision.status === 'revisions_requested'"
                class="font-semibold text-blue-900"
            >
                Revisions requested for this Scope of Work update.
            </div>

            <div class="mt-1 text-blue-800">
                Version:
                <span class="font-semibold">{{ status.active_revision.version_label }}</span>

                <template v-if="status.active_revision.base_version">
                    based on version
                    <span class="font-semibold">
                        {{ status.active_revision.base_version.version_label }}
                    </span>
                </template>
            </div>

            <div
                v-if="status.active_revision.submission"
                class="mt-1 text-blue-800"
            >
                Submission status:
                <span class="font-semibold">
                    {{ status.active_revision.submission.status }}
                </span>
            </div>

            <div class="mt-2 text-blue-800">
                {{ status.active_revision.summary.total_changes }} change(s) detected.

                <template v-if="status.active_revision.summary.requires_submission">
                    Some changes require approval.
                </template>

                <template v-else>
                    These changes can be finalized without approval.
                </template>
            </div>

            <ul class="mt-2 list-inside list-disc text-blue-900">
                <li
                    v-for="change in status.active_revision.changes"
                    :key="change.id"
                >
                    {{ change.label }}
                    <template v-if="change.entity_label">— {{ change.entity_label }}</template>
                    <span v-if="change.requires_approval === 'yes'" class="font-semibold">— requires approval</span>
                    <span v-else-if="change.requires_approval === 'conditional'" class="font-semibold">— may require approval</span>
                </li>
            </ul>

            <div class="mt-3 flex flex-wrap gap-2">
                <button
                    v-if="
                        status.active_revision.status === 'draft' &&
                        status.active_revision.summary.can_finalize_without_approval
                    "
                    type="button"
                    class="btn btn-xs"
                    @click="$emit('finalize', status.active_revision)"
                >
                    Finalize as version {{ status.active_revision.version_label }}
                </button>

                <button
                    v-if="
                        ['draft', 'revisions_requested'].includes(status.active_revision.status) &&
                        status.active_revision.summary.requires_submission
                    "
                    type="button"
                    class="btn btn-xs"
                    @click="$emit('submit', status.active_revision)"
                >
                    {{ status.active_revision.status === 'revisions_requested'
                        ? 'Resubmit for approval'
                        : 'Submit for approval'
                    }}
                </button>

                <template v-if="canManage && status.active_revision.status === 'submitted'">
                    <button
                        type="button"
                        class="btn btn-xs"
                        @click="$emit('approve', status.active_revision)"
                    >
                        Approve revision
                    </button>

                    <button
                        type="button"
                        class="btn btn-xs"
                        @click="$emit('request-revisions', status.active_revision)"
                    >
                        Request revisions
                    </button>
                </template>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
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

defineEmits(['finalize', 'submit', 'approve', 'request-revisions']);
</script>