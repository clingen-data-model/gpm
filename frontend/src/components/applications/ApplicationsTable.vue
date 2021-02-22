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
            applications: 'applications'
        }),
        sort: {
            immediate: true,
            get() {
                if (Object.keys(this.$route.query).includes('sort-field')) {
                    return {
                        field: this.fields.find(i => i.name == this.$route.query['sort-field']),
                        desc: Boolean(parseInt(this.$route.query['sort-desc']))
                    }
                }
                return {
                    field: this.fields.find(i => i.name == 'name'),
                    desc: false
                }
            },
            set(sortObj) {
                const newSortQuery = {'sort-field': sortObj.field.name, 'sort-desc': sortObj.desc ? 1 : 0}

                const newQuery = {
                    ...this.$route.query, 
                    ...newSortQuery
                };
 
                this.$router.replace({path: this.$route.path, query: newQuery})
            }
        },
        filteredData() {
            return this.applications.filter(item => {
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

            this.$store.dispatch('getApplications', params);
        },
        goToApplication (item) {
            this.$router.push({name: 'ApplicationDetail', params: {uuid: item.uuid}})
        },
    },
    mounted () {
        this.getApplications()
    }
}
</script>