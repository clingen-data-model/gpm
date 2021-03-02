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
            v-model:sort="sort"
        >
        </data-table>
    </div>
</template>
<script>
import { mapGetters } from 'vuex'
import { formatDate } from '../../date_utils'
import SortAndFilter from '../../composables/router_aware_sort_and_filter'

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
                        if (item && item.latest_log_entry) {
                            return formatDate(item.latest_log_entry.created_at);
                        }
                        return null
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
        }
    },
    computed: {
        ...mapGetters({
            applications: 'applications/all'
        }),
        filteredData() {
            return this.applications
                .filter(item => !this.epTypeId || item.ep_type_id == this.epTypeId)
                .filter(item => {
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
    },
    methods: {

        getApplications () {
            const params = {
                with: [
                    'latestLogEntry',
                    'latestPendingNextAction',
                    'type'
                ],
            }

            const where = {};

            if (Object.keys(where).length > 0) {
                params.where = where;
            }

            this.$store.dispatch('applications/getApplications', params);
        },
        goToApplication (item) {
            this.$router.push({name: 'ApplicationDetail', params: {uuid: item.uuid}})
        },
    },
    mounted () {
        this.getApplications()
    },
    setup() {
        return SortAndFilter()
    }
}
</script>