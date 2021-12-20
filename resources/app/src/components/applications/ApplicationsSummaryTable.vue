<template>
    <div>
        <div class="flex justify-between">
            <div class="mb-1 flex space-x-2">
                <label>Filter: <input type="text" class="sm" v-model="filter" placeholder="filter"></label>
                <checkbox 
                    v-model="showCompleted"
                    label="Show completed"
                />
            </div>
        </div>
        <data-table 
            :data="filteredData" 
            :fields="selectedFields" 
            :filter-term="filter" 
            :row-click-handler="goToApplication"
            row-class="cursor-pointer"
            v-model:sort="sort"
            :style="remainingHeight"
            class="overflow-auto text-xs"
            ref="table"
        >
            <template v-slot:cell-contacts="{item}">
                <ul>
                    <li v-for="c in item.contacts" :key="c.id">
                        <small><a :href="`mailto:${c.email}`" class="text-blue-500">{{c.name}}</a></small>
                    </li>
                </ul>
            </template>
            <template v-slot:cell-latest_log_entry_description="{value}">
                <div v-html="value"></div>
            </template>
            <template v-slot:cell-latest_pending_next_action_entry="{value}">
                <div v-html="value"></div>
            </template>
        </data-table>
    </div>
</template>
<script>
import { mapGetters } from 'vuex'
import sortAndFilter from '@/composables/router_aware_sort_and_filter'

export default {
    name: 'SummaryVceps',
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
                    name: 'name',
                    label: 'Name',
                    type: String,
                    sortable: true
                },
                {
                    name: 'cdwg.name',
                    label: 'CDWG',
                    type: String,
                    sortable: true,
                    resolveValue (item) {
                        return item.cdwg ? item.cdwg.name : '';
                    }
                },
                {
                    name: 'current_step',
                    label: 'Step',
                    type: Number,
                    sortable: true,
                    resolveValue (item) {
                        return item.isCompleted ? 'Completed' : item.current_step
                    },
                    resolveSort (item) {
                        return item.isCompleted ? 5 : item.current_step
                    }
                },
                {
                    name: 'contacts',
                    label: 'Contacts',
                    type: Array,
                    sortable: false,
                    class: ['min-w-40'],
                    step: 1
                },
                {
                    name: 'first_scope_document.date_received',
                    label: this.epTypeId == 2 ? 'Step 1 Received' : 'Application Received',
                    type: Date,
                    sortable: true,
                    class: ['min-w-28'],
                    step: 1

                },
                {
                    name: 'approval_dates.step 1',
                    label: this.epTypeId == 2 ? 'Step 1 Approved' : 'Application Approved',
                    type: Date,
                    sortable: true,
                    class: ['min-w-28'],
                    step: 1
                },
                {
                    name: 'approval_dates.step 2',
                    label: 'Step 2 Approved',
                    type: Date,
                    sortable: true,
                    class: ['min-w-28'],
                    step: 2
                },
                {
                    name: 'approval_dates.step 3',
                    label: 'Step 3 Approved',
                    type: Date,
                    sortable: true,
                    class: ['min-w-28'],
                    step: 3
                },
                {
                    name: 'first_final_document.date_received',
                    label: 'Step 4 Received',
                    type: Date,
                    sortable: true,
                    class: ['min-w-28'],
                    step: 4
                },
                {
                    name: 'approval_dates.step 4',
                    label: 'Step 4 Approved',
                    type: Date,
                    sortable: true,
                    class: ['min-w-28'],
                    step: 4
                }
            ]
        }
    },
    computed: {
        ...mapGetters({
            applications: 'applications/all'
        }),
        filteredData() {
            return this.applications
                .filter(item => !this.epTypeId || item.expert_panel_type_id == this.epTypeId)
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
        selectedFields() {
            const stepsToShow = this.epTypeId == 2 ? [1,2,3,4] : [1]
            return this.fields.filter(field => !field.step || stepsToShow.includes(field.step))
        },
        showAllInfo: {
            immediate: true,
                get() {
                    if (Object.keys(this.$route.query).includes('showAllInfo')) {
                        return this.$route.query.showAllInfo
                    }
                    return 0
                },
                set(newValue) {
                    const newQuery = {...this.$route.query};
                    newQuery.showAllInfo = newValue

                    this.$router.replace({path: this.$route.path, query: newQuery})
                }            
        },
        remainingHeight () {
            return {height: 'calc(100vh - 220px)'}
        }
    },
    methods: {

        getApplications () {
            const params = {
                with: [
                    'latestLogEntry',
                    'latestPendingNextAction',
                    'type',
                    'contacts',
                    'firstScopeDocument',
                    'firstFinalDocument'
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
        const {sort, filter} = sortAndFilter();
        // const showAllInfo = computedQueryParam('showAllInfo');

        return {
            sort,
            filter,
            // showAllInfo
        }
    }
}
</script>