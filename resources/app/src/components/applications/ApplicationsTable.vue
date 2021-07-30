<template>
    <div>
        <div class="sm:flex justify-between">
            <div class="mb-1 sm:flex space-x-2">
                <label class="block">
                    Filter: 
                    <input type="text" class="sm" v-model="filter" placeholder="filter">
                </label>
                <label class="block">
                    Waiting on: 
                    <select v-model="waitingOn" class="sm">
                        <option :value="null">Any</option>
                        <option v-for="i in assignees" :key="i.id"
                            :value="i.id"
                        >
                            {{i.name}}
                        </option>
                    </select>
                </label>
                <label class="block">
                    <input type="checkbox" v-model="showCompleted">
                    Show completed
                </label>

                <!-- <label class="block"><input type="checkbox" v-model="showDeleted">Show Deleted</label> -->

            </div>
            <div>
                <button class="btn btn-xs" :class="{blue: showAllInfo == 0}" @click="showAllInfo = 0">Summary</button>
                <button class="btn btn-xs" :class="{blue: showAllInfo == 1}" @click="showAllInfo = 1">All Info</button>
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
            class="overflow-auto"
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
            <template v-slot:cell-next_actions="{item}">
                <div>
                    <div v-for="assignee in assignees.filter(i => item.pendingActionsByAssignee[i.id].length > 0)" :key="assignee.id">
                        <span>
                            {{assignee.short_name}}: 
                            <strong>
                                {{item.pendingActionsByAssignee[assignee.id].length}}
                            </strong>
                        </span>
                    </div>
                </div>
            </template>
        </data-table>
    </div>
</template>
<script>
import { mapGetters } from 'vuex'
import { formatDate } from '@/date_utils'
import sortAndFilter from '@/composables/router_aware_sort_and_filter'
import configs from '@/configs'

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
                    sortable: true,
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
                    sortable: true,
                    resolveValue (item) {
                        return item.cdwg ? item.cdwg.name : '';
                    }
                },
                {
                    name: 'current_step',
                    label: 'Current Step',
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
                    colspan: 2,
                    headerClass: ['max-w-sm'],
                    class: ['min-w-28']
                },
                {
                    name: 'latest_log_entry.description',
                    label: 'Last Activity',
                    type: String,
                    hideHeader: true,
                    class: ['max-w-48', 'truncate']
                },
                {
                    name: 'next_actions',
                    label: 'Next Actions',
                    type: String,
                    sortable: false,
                    class: ['min-w-28', 'max-w-xs', 'truncate'],

                }
            ],
            allInfoFields: [
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
            ],
            waitingOn: null,
            showDeleted: false
        }
    },
    computed: {
        ...mapGetters({
            applications: 'applications/all'
        }),
        filteredData() {
            let applications = this.applications
                .filter(item => !this.epTypeId || item.ep_type_id == this.epTypeId)
                .filter(item => {
                    if (!this.showCompleted) {
                        return item.date_completed == null;
                    }
                    return true;
                })

            if (this.waitingOn) {
                console.info(this.waitingOn)
                applications = applications.filter(app => app.pendingActionsByAssignee[this.waitingOn].length > 0);
            }

            if (!this.showDeleted) {
                applications = applications.filter(app => app.deleted_at === null);
            }

            return applications

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
            if (this.showAllInfo == 1) {
                const stepsToShow = this.epTypeId == 2 ? [1,2,3,4] : [1]
                const allInfoFields = this.allInfoFields.filter(field => stepsToShow.includes(field.step))
                return [...this.fields, ...allInfoFields]
            }
            return this.fields
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
        },
        assignees () {
            return Object.values(configs.nextActions.assignees);
        }
    },
    methods: {

        getApplications () {
            const params = {
                with: [
                    'latestLogEntry',
                    'nextActions',
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
            if (this.showDeleted) {
                params.showDeleted = 1;
            }

            this.$store.dispatch('applications/getApplications', params);
        },
        goToApplication (item) {
            if (item.deleted_at) {
                alert ('The application for '+item.name+' has been deleted.  Details cannot be viewed.');
                return;
            }
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