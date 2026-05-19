<template>
  <div v-if="status" class="rounded-md border p-4 mb-4">
    <div v-if="!status.has_approved_version">
      <strong>Scope of Work versioning has not been initialized for this group.</strong>
    </div>

    <div v-else-if="!status.has_active_revision">
      <strong>This application is approved.</strong>
      <div class="text-sm text-gray-600">
        Current Scope of Work version: {{ status.approved_version.version_label }}.
        Scope of Work changes will be tracked and versioned.
      </div>
    </div>

    <div v-else>
      <strong>Draft Scope of Work changes detected.</strong>

      <div class="text-sm text-gray-600">
        Draft version: {{ status.active_revision.version_label }}
        based on version {{ status.active_revision.base_version?.version_label }}.
      </div>

      <ul class="mt-2 text-sm list-disc list-inside">
        <li
          v-for="change in status.active_revision.changes"
          :key="change.id"
        >
          {{ change.label }}
          <span v-if="change.entity_label">: {{ change.entity_label }}</span>
          <span v-if="change.requires_approval === 'yes'" class="font-semibold">
            — requires approval
          </span>
          <span v-else-if="change.requires_approval === 'conditional'" class="font-semibold">
            — may require approval
          </span>
        </li>
      </ul>

      <div class="mt-3">
        <button
            v-if="status.active_revision.summary.can_finalize_without_approval"
            type="button"
            class="btn btn-xs"
            @click="$emit('finalize', status.active_revision)"
        >
            Finalize as version {{ status.active_revision.version_label }}
        </button>

        <button
          v-else-if="
              status.active_revision.status === 'draft' &&
              status.active_revision.summary.requires_submission
          "
          type="button"
          class="btn btn-xs"
          @click="$emit('submit', status.active_revision)"
        >
          Submit for approval
        </button>
        <span
          v-else-if="status.active_revision.status === 'submitted'"
          class="text-sm font-semibold text-blue-900"
        >
          Submitted for approval.
        </span>
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
});

defineEmits(['finalize', 'submit']);
</script>