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
                    <!-- <router-link :to="`log-entries/${item.id}/edit`" class="btn btn-xs inline-block"> -->
                        <button class="btn btn-xs"  @click="editLogEntry(item)">
                            <icon-edit width="12"></icon-edit>
                        </button>
                    <!-- </router-link> -->
                    <router-link 
                        :to="`log-entries/${item.id}/edit`" 
                        v-if="item.activity_type === null"
                        class="btn btn-xs inline-block"
                    >
                        <icon-trash width="12"></icon-trash>
                    </router-link>
                </div>
            </template>
            <template v-slot:cell-description="{item}">
                <div v-html="item.description" class="links-blue"></div>
            </template>
        </data-table>
        <modal-dialog v-model="editingEntry" title="Edit log entry">
            <log-entry-form :log-entry="selectedLogEntry"></log-entry-form>
        </modal-dialog>
    </div>
</template>
<script>
import { ref, reactive, computed } from 'vue'
import { useStore } from 'vuex';
import IconEdit from '@/components/icons/IconEdit'
import IconTrash from '@/components/icons/IconTrash'
import LogEntryForm from '@/components/ActivityLogEntryForm'


const fields = [
    {
        name: 'created_at',
        label: 'Created',
        sortName: 'created_at',
        sortable: true,
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
        IconEdit,
        IconTrash,
        LogEntryForm
    },
    props: {
        logEntries: {
            required: true,
            type: Array,
        }
    },
    setup (props) {
        const sort = ref({
            field: 'created_at',
            desc: true
        });
        const selectedLogEntry = ref({});
        const editingEntry = ref(false);

        const hasLogEntries = computed(() => {
            return props.logEntries.length > 0;
        });

        const editLogEntry = entry => {
            editingEntry.value = true;
            selectedLogEntry.value = entry;
        };

        return {
            fields: ref(fields),
            sort,
            selectedLogEntry,
            editingEntry,
            hasLogEntries,
            editLogEntry
        }
    }
}
</script>