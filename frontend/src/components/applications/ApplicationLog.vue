<template>
    <div>
        <div v-if="logEntries.length == 0">
            No log entries
        </div>
        <data-table :fields="fields" :data="logEntries" v-model:sort="sort" v-else>
        </data-table>
    </div>
</template>
<script>
import axios from 'axios'
import {formatDate} from '../../date_utils'

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
        uuid: {
            required: true,
            type: String
        },
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
    methods: {
        async getLogEntries() {
            axios.get(`/api/applications/${this.uuid}/log-entries`)
                .then(response => {
                    this.logEntries = response.data
                })
        }
    },
    mounted() {
        this.getLogEntries();
    }
}
</script>