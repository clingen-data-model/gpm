<script>
import ActivityLogDeleteConfirmation from '@/components/log_entries/ActivityLogDeleteConfirmation.vue'

import LogEntryForm from '@/components/log_entries/ActivityLogEntryForm.vue'
import {formatDate, formatTime} from '@/date_utils'
import { computed, ref } from 'vue'

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
        // DATA
        const sort = ref({
            field: 'created_at',
            desc: true
        });
        const selectedEntry = ref({});
        const editingEntry = ref(false);
        const showDeleteConfirmation = ref(false);

        // COMPUTED
        const hasLogEntries = computed(() => {
            return props.logEntries.length > 0;
        });

        // METHODS
        const editLogEntry = entry => {
            editingEntry.value = true;
            selectedEntry.value = entry;
        };

        const closeEntryForm = () => {
            editingEntry.value = false;
            selectedEntry.value = {};
        }
        
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
<template>
    <div>
        <div v-if="!hasLogEntries" class="px-3 py-2 rounded border border-gray-300 text-gray-500 bg-gray-200">
            No log entries to display
        </div>
        <data-table v-else v-model:sort="sort" :fields="fields" :data="logEntries">
            <template #cell-id="{item}">
                <div v-if="hasPermission('groups-manage')" class="flex space-x-1">
                    <button class="btn btn-xs inline-block" @click="editLogEntry(item)">
                        <icon-edit width="12"></icon-edit>
                    </button>
                    <button 
                        v-if="item.activity_type === null"
                        class="btn btn-xs inline-block"
                        @click="confirmDelete(item)"
                    >
                        <icon-trash width="12"></icon-trash>
                    </button>
                </div>
            </template>
            <template #cell-description="{item}">
                <div class="links-blue" v-html="item.description"></div>
            </template>
        </data-table>
        <modal-dialog v-model="editingEntry" title="Edit log entry">
            <LogEntryForm 
                :log-entry="selectedEntry" 
                :api-url="apiUrl"
                @saved="closeEntryForm"
                @canceled="closeEntryForm"
            ></LogEntryForm>
        </modal-dialog>

        <modal-dialog v-model="showDeleteConfirmation" title="Delete this log entry?">
            <ActivityLogDeleteConfirmation 
                :logEntry="selectedEntry" 
                :api-url="apiUrl"
                @canceled="closeDeleteConfirmation"
                @deleted="closeDeleteConfirmation"
            ></ActivityLogDeleteConfirmation>
        </modal-dialog>
    </div>
</template>
<style lang="postcss">
    .links-blue a {
        @apply text-blue-500;
    }
</style>