<template>
    <div>
        <div class="px-3 py-2 rounded border border-gray-300 text-gray-500 bg-gray-200" v-if="!hasLogEntries">
            {{noResultsMessage}}
        </div>
        <data-table :fields="fields" :data="filteredLogEntries" v-model:sort="sort" v-else>
        </data-table>
    </div>
</template>
<script>
import {mapGetters} from 'vuex';
import {formatDate} from '../../date_utils';

const fields = [
                {
                    name: 'created_at',
                    label: 'Created',
                    sortable: true,
                    type: String,
                    resolveValue: (item) => formatDate(item.created_at)
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
                }
            ];

export default {
    props: {
        step: {
            required: false,
            type: Number,
        }
    },
    data() {
        return {
            fields: fields,
            logEntries: [],
            sort: {
                field: fields.find(i => i.name = 'created_at'),
                desc: true
            }
        }
    },
    computed: {
        ...mapGetters({
            application: 'currentItem'
        }),
        hasLogEntries(){
            return this.filteredLogEntries.length > 0;
        },
        filteredLogEntries() {
            if(this.step && this.application && this.application.log_entries) {
                return this.application.log_entries.filter(entry => entry.properties.step == this.step);
            }
            return [];
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
            await this.$store.dispatch('getApplicationWithLogEntries', this.application.uuid)
        }
    },
    mounted() {
        // this.getLogEntries()
    }
}
</script>