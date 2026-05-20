<template>
    <div v-if="comparison" class="rounded border bg-white p-4 mb-4">
        <div class="flex items-start justify-between">
            <div>
                <h3 class="mb-1">
                    Comparing version
                    {{ comparison.from_version.version_label }}
                    →
                    {{ comparison.to_version.version_label }}
                </h3>

                <div class="text-sm text-gray-600">
                    {{ comparison.summary.total_changes }} change(s),
                    {{ comparison.summary.major_changes }} major,
                    {{ comparison.summary.minor_changes }} minor.
                </div>
            </div>

            <button type="button" class="btn btn-xs" @click="$emit('close')">
                Close
            </button>
        </div>

        <div v-if="comparison.changes.length === 0" class="well mt-3">
            No Scope of Work changes detected between these versions.
        </div>

        <ul v-else class="mt-3 list-inside list-disc text-sm">
            <li
                v-for="(change, index) in comparison.changes"
                :key="index"
            >
                {{ change.label }}

                <template v-if="change.entity_label">
                    — {{ change.entity_label }}
                </template>

                <span
                    v-if="change.requires_approval === 'yes'"
                    class="font-semibold text-red-700"
                >
                    — required approval
                </span>

                <span
                    v-else-if="change.requires_approval === 'conditional'"
                    class="font-semibold text-yellow-700"
                >
                    — conditional approval
                </span>
            </li>
        </ul>
    </div>
</template>

<script setup>
defineProps({
    comparison: {
        type: Object,
        required: false,
        default: null,
    },
});

defineEmits(['close']);
</script>