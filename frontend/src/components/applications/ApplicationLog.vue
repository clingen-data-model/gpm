<template>
    <div>
        <div v-if="!hasLogEntries">
            No log entries
        </div>
        <data-table :fields="fields" :data="application.log_entries" v-model:sort="sort" v-else>
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
        steps: {
            required: false,
            type: [Number,Array],
            default: function () {
                return [1,2,3,4]
            }
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
            if (this.application && this.application.log_entries) {
                return this.application.log_entries.length > 0;
            }
            return false;

        }
    },
    methods: {
        async getLogEntries() {
            await this.$store.dispatch('getApplicationWithLogEntries', this.application.uuid)
        }
    },
    mounted() {
        this.getLogEntries()
    }
}
</script>