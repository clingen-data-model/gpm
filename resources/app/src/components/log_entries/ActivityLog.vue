<style lang="postcss">
    .links-blue a {
        @apply text-blue-500;
    }
</style>
<template>
    <div>
        <div class="px-3 py-2 rounded border border-gray-300 text-gray-500 bg-gray-200" v-if="!hasLogEntries">
            No log entries to display
        </div>
        <data-table :fields="fields" :data="logEntries" v-model:sort="sort" v-else>
            <template v-slot:cell-id="{item}">
                <div class="flex space-x-1" v-if="hasPermission('groups-manage')">
                    <button class="btn btn-xs inline-block"  @click="editLogEntry(item)">
                        <icon-edit width="12"></icon-edit>
                    </button>
                    <button 
                        @click="confirmDelete(item)"
                        v-if="item.activity_type === null"
                        class="btn btn-xs inline-block"
                    >
                        <icon-trash width="12"></icon-trash>
                    </button>
                </div>
            </template>
            <template v-slot:cell-description="{item}">
                <div v-html="item.description" class="links-blue"></div>
            </template>
        </data-table>
        <modal-dialog v-model="editingEntry" title="Edit log entry">
            <log-entry-form 
                :log-entry="selectedEntry" 
                :api-url="apiUrl"
                @saved="closeEntryForm"
                @canceled="closeEntryForm"
            ></log-entry-form>
        </modal-dialog>

        <modal-dialog v-model="showDeleteConfirmation" title="Delete this log entry?">
            <activity-log-delete-confirmation 
                :logEntry="selectedEntry" 
                :api-url="apiUrl"
                @canceled="closeDeleteConfirmation"
                @deleted="closeDeleteConfirmation"
            ></activity-log-delete-confirmation>
        </modal-dialog>
    </div>
</template>
<script>
import { ref, computed } from 'vue'

import LogEntryForm from '@/components/log_entries/ActivityLogEntryForm'
import ActivityLogDeleteConfirmation from '@/components/log_entries/ActivityLogDeleteConfirmation'
import {formatDate, formatTime} from '@/date_utils'

const fields = [
    {
        name: 'created_at',
        label: 'Date & Time',
        sortName: 'created_at',
        sortable: true,
        resolveValue ({created_at}) {
            return (created_at) ? formatDate(created_at) : null;
        },
        type: Date,
        colspan: 2,
        class: 'w-16'
    },
    {
        name: 'created_at',
        label: 'Time',
        sortName: 'created_at',
        hideHeader: true,
        sortable: false,
        resolveValue ({created_at}) {
            return (created_at) ? formatTime(created_at) : null;
        },
        class: "w-28",
        type: Date,
    },
    {
        name: 'description',
        label: 'Entry',
        sortable: true,
        type: String
    },
    {
        name: 'causer.name',
        label: 'User',
        sortable: true
    },
    {
        name: 'id',
        label: '',
        sortable: false,
    }
];

export default {
    components: {
        LogEntryForm,
        ActivityLogDeleteConfirmation
    },
    props: {
        logEntries: {
            required: true,
            type: Array,
        },
        apiUrl: {
            required: true,
            type: String
        }
    },
    setup (props) {
        const sort = ref({
            field: 'created_at',
            desc: true
        });
        const selectedEntry = ref({});
        const editingEntry = ref(false);

        const hasLogEntries = computed(() => {
            return props.logEntries.length > 0;
        });

        const editLogEntry = entry => {
            editingEntry.value = true;
            selectedEntry.value = entry;
        };

        const closeEntryForm = () => {
            editingEntry.value = false;
            selectedEntry.value = {};
        }

        const showDeleteConfirmation = ref(false);
        const confirmDelete = (entry) => {
            selectedEntry.value = entry;
            showDeleteConfirmation.value = true;
        }
        const closeDeleteConfirmation = () => {
            showDeleteConfirmation.value = false;
            selectedEntry.value = {};
        }

        return {
            fields: ref(fields),
            sort,
            selectedEntry,
            editingEntry,
            hasLogEntries,
            editLogEntry,
            closeEntryForm,
            showDeleteConfirmation,
            confirmDelete,
            closeDeleteConfirmation
        }
    }
}
</script>