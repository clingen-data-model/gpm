<template>
    <div class="space-y-4">
        <div v-if="!history">
            Loading Scope of Work history...
        </div>

        <div v-else-if="history.versions.length === 0" class="well">
            No Scope of Work versions have been created yet.
        </div>

        <template v-else>
            <div
                v-for="version in history.versions"
                :key="version.uuid"
                class="rounded border bg-white p-4"
            >
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="mb-1">
                            Version {{ version.version_label }}
                            <span class="text-sm font-normal text-gray-500">
                                — {{ version.status.replace('_', ' ') }}
                            </span>
                        </h3>

                        <div class="text-sm text-gray-600">
                            <template v-if="version.base_version">
                                Based on version {{ version.base_version.version_label }}.
                            </template>

                            <template v-if="version.submission">
                                Submission: {{ version.submission.type }}
                                — {{ version.submission.status }}.
                            </template>
                        </div>

                        <div class="mt-1 text-sm text-gray-600">
                            <template v-if="version.approved_at">
                                Approved {{ version.approved_at }}
                            </template>
                            <template v-else-if="version.submitted_at">
                                Submitted {{ version.submitted_at }}
                            </template>
                            <template v-else>
                                Created {{ version.created_at }}
                            </template>
                        </div>
                    </div>

                    <div class="text-right text-sm text-gray-600">
                        <div>{{ version.summary.total_changes }} change(s)</div>
                        <div>{{ version.summary.major_changes }} major</div>
                        <div>{{ version.summary.minor_changes }} minor</div>
                    </div>
                </div>

                <ul v-if="version.changes.length" class="mt-3 list-inside list-disc text-sm">
                    <li
                        v-for="change in version.changes"
                        :key="change.id"
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
    </div>
</template>

<script setup>
defineProps({
    history: {
        type: Object,
        required: false,
        default: null,
    },
});
</script>