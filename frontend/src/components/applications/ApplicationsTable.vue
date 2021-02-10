<template>
    <div>
        <div class="mb-1 flex space-x-2">
            <label>Filter: <input type="text" class="sm" v-model="filter" placeholder="filter"></label>
            <label>
                <input type="checkbox" v-model="showCompleted">
                Show completed
            </label>
        </div>
        <data-table 
            :data="filteredData" 
            :fields="fields" 
            :filter-term="filter" 
            class="width-full"
            :row-click-handler="goToApplication"
            row-class="cursor-pointer"
        >
            <template v-slot:latest_activity_log="props">
                <pre>{{props.value.description}}</pre>
            </template>
        </data-table>
    </div>
</template>
<script>
import { all as getAllApplications } from '../../adapters/application_repository'
import { formatDate } from '../../date_utils'

export default {
    components: {
    },
    props: {
        epTypeId: {
            type: Number,
            default: null
        }
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
                    name: 'latest_log_entry.created_at',
                    label: 'Last Activity',
                    type: String,
                    sortable: true,
                    resolveValue (item) {
                        return formatDate(item.latest_log_entry.created_at);
                    },
                    colspan: 2
                },
                {
                    name: 'latest_log_entry.description',
                    label: 'Last Activity',
                    type: String,
                    hideHeader: true
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
        filteredData() {
            return this.data.filter(item => {
                if (!this.showCompleted) {
                    return item.date_completed == null;
                }
                return true;
            })
        },
        showCompleted: {
            set(value) {
                let currentQuery = this.$route.query;
                let currentPath = this.$route.path;
                
                let updatedQuery = {...currentQuery};

                if (!value) {
                    delete updatedQuery['show-completed'];
                } else {
                    updatedQuery = {...currentQuery, ...{'show-completed': 1} };
                }

                this.$router.replace({path: currentPath, query: updatedQuery})
            },
            get() {
                return Boolean(parseInt(this.$route.query['show-completed']))
            },
            immediate: true
        },
        filter: {
            set(value) {
                let currentQuery = this.$route.query;
                let currentPath = this.$route.path;

                let updatedQuery = {...currentQuery};

                if (!value) {
                    delete updatedQuery.filter;
                } else {
                    updatedQuery = {...currentQuery, ...{'filter': value} };
                }

                this.$router.replace({path: currentPath, query: updatedQuery})
            },
            get() {
                return this.$route.query.filter
            },
            immediate: true
        }
    },
    methods: {
        async getApplications () {
            const params = {
                with: [
                    'latestLogEntry',
                    'latestPendingNextAction',
                    'type'
                ],
            }

            const where = {};

            if (this.epTypeId) {
                where.ep_type_id = this.epTypeId;
            }

            if (Object.keys(where).length > 0) {
                params.where = where;
            }

            this.data = await getAllApplications(params)
        },
        goToApplication (item) {
            // console.info('go to application!!', item)
            this.$router.push({name: 'ApplicationDetail', params: {uuid: item.uuid}})
        }
    },
    mounted () {
        this.getApplications()
    }
}
</script>