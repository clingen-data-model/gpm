<style lang="postcss">
    .links-blue a {
        @apply text-blue-500;
    }
</style>
<template>
    <div>
        <div class="px-3 py-2 rounded border border-gray-300 text-gray-500 bg-gray-200" v-if="!hasLogEntries">
            {{noResultsMessage}}
        </div>
        <data-table :fields="fields" :data="filteredLogEntries" v-model:sort="sort" v-else>
            <template v-slot:cell-id="{item}">
                <div class="flex space-x-1">
                    <router-link :to="{name: 'EditLogEntry', params:{id: item.id}}" class="btn btn-xs inline-block">
                        <icon-edit width="12"></icon-edit>
                    </router-link>
                    <router-link 
                        :to="{name: 'ConfirmDeleteLogEntry', params:{id: item.id}}" 
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
            <!-- <template v-slot:cell-step="{item}">
                <pre>
                    {{item}} 
                </pre>
            </template> -->
        </data-table>
        <modal-dialog v-model="editingEntry" title="Edit log entry">
            <log-entry-form></log-entry-form>
        </modal-dialog>
    </div>
</template>
<script>
import {computed, watch} from 'vue'
import {useStore} from 'vuex';


import LogEntryForm from '@/components/log_entries/LogEntryForm.vue'
import {logEntries, fetchEntries} from '@/adapters/log_entry_repository';


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
        LogEntryForm
    },
    props: {
        step: {
            required: false,
            type: Number,
        }
    },
    data() {
        return {
            sort: {
                field: 'created_at',
                desc: true
            },
            selectedLogEntry: {},
            editingEntry: false,
        }
    },
    computed: {
        fields () {
            if (this.group.group_type_id == 4 && !fields.map(f => f.name).includes('step')) {
                fields.splice(2, 0, {
                    name: 'step',
                    sortable: true,
                })
            }

            return fields;
        },
        hasLogEntries(){
            return this.filteredLogEntries.length > 0;
        },
        filteredLogEntries() {
            if(this.logEntries && this.step) {
                return this.logEntries.filter(entry => entry.step == this.step);
            }

            if (this.logEntries) {
                return this.logEntries;
            }

            return []
        },
        noResultsMessage() {
            if (typeof this.step == 'number') {
                return `No progress log entries have been entered for step ${this.step}`;
            }

            return 'There are no log entries for this application';
        }
    },
    methods: {
        async getLogEntries() {
            await fetchEntries();
        },
        editLogEntry(entry) {
            this.editingEntry = true;
            this.selectedLogEntry = entry;
        }
    },
    mounted() {
        // this.getLogEntries()
    },
    setup () {
        const store  = useStore();
        const group = computed(() => {
            return store.getters['groups/currentItemOrNew']
        })

        const application = computed(() => {
            return group.value.expert_panel;
        })

        watch(group, () => {
            if (group.value.uuid) {
                fetchEntries(`/api/groups/${group.value.uuid}/activity-logs`)
            }
        }, {
            immediate: true
        })
        return {
            group,
            application,
            logEntries
        }
    }
}
</script>