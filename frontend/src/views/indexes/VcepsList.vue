<template>
    <div>
        <div class="mb-1">
            <label>Filter: <input type="text" class="sm" v-model="filter" placeholder="filter"></label>
        </div>
        <data-table :data="data" :fields="fields" :filter-term="filter">
            <template v-slot:latest_activity_log="props">
                <pre>{{props.value.description}}</pre>
            </template>
        </data-table>
    </div>
</template>
<script>
import { all as getAllApplications } from '../../adapters/application_repository'

export default {
    components: {
    },
    props: {
        
    },
    data() {
        return {
            fields: [
                {
                    name: 'id',
                    label: 'ID',
                    type: Number,
                    sortable: true
                },
                {
                    name: 'name',
                    label: 'Name',
                    type: String,
                    sortable: true
                },
                {
                    name: 'cdwg.name',
                    label: 'CDWG',
                    type: String,
                    sortable: true
                },
                {
                    name: 'current_step',
                    label: 'Current Step',
                    type: Number,
                    sortable: true
                },
                {
                    name: 'latest_log_entry.description',
                    label: 'Last Activity',
                    type: Date,
                },
                {
                    name: 'latest_pending_next_action.entry',
                    label: 'Next Action',
                    type: String,
                    sortable: false

                }
            ],
            data: [],
        }
    },
    computed: {
        filter: {
            set(value) {
                let currentQuery = this.$route.query;
                let currentPath = this.$route.path;

                this.$router.replace({path: currentPath, query: {...currentQuery, ...{filter: value}}})
            },
            get() {
                return this.$route.query.filter
            },
            immediate: true
        }
    },
    methods: {
        async getApplications () {
            this.data = await getAllApplications({
                with: [
                    'latestLogEntry',
                    'latestPendingNextAction'
                ],
                where: {
                    ep_type_id: 2
                }
            })
        }
    },
    mounted () {
        this.getApplications()
    }
}
</script>