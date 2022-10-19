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
                <checkbox
                    v-model="showCompleted"
                    label="Show completed"
                />
                <!-- <checkbox
                    v-model="showDeleted"
                    label="Show Deleted"
                /> -->
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
                    <li v-for="c in item.group.members" :key="c.id">
                        <small><a :href="`mailto:${c.person.email}`" class="text-blue-500">{{c.person.name}}</a></small>
                    </li>
                </ul>
            </template>
            <template v-slot:cell-latest_log_entry="{item}">
                <popper hover arrow placement="right">
                    <template v-slot:content>
                        <div  v-html="item.group.latest_log_entry.description"></div>
                    </template>
                    {{formatDate(item.group.latest_log_entry.created_at)}}
                </popper>
            </template>
            <template v-slot:cell-next_actions="{item}">
                <popper hover arrow placement="left">
                    <template v-slot:content>
                        <div
                            v-for="assignee in assignees.filter(i => item.pendingActionsByAssignee[i.id].length > 0)"
                            :key="assignee.id"
                            class="whitespace-normal max-w-80"
                        >
                            <h4>{{assignee.short_name}}:</h4>
                            <ul class="list-disc pl-6 text-sm">
                                <li v-for="action in item.pendingActionsByAssignee[assignee.id]" :key="action.id" v-html="action.entry" class="w-76 whitespace-normal">
                                </li>
                            </ul>
                        </div>
                    </template>
                    <div v-for="assignee in assignees.filter(i => item.pendingActionsByAssignee[i.id].length > 0)" :key="assignee.id">
                        <span>
                            {{assignee.short_name}}:
                            <strong>
                                {{item.pendingActionsByAssignee[assignee.id].length}}
                            </strong>
                        </span>
                    </div>
                </popper>
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
                    name: 'full_name',
                    label: 'Name',
                    type: String,
                    sortable: true
                },
                {
                    name: 'group.parent.name',
                    label: 'CDWG',
                    type: String,
                    sortable: true,
                    resolveValue (item) {
                        return (item.group && item.group.parent)? item.group.parent.name : '';
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
                    name: 'latest_submission_date',
                    label: 'Last Submitted',
                    type: Date,
                    sortable: true,
                    resolveValue (item) {
                        return item.group.latest_submission
                            ? formatDate(item.group.latest_submission.created_at)
                            : null
                    }
                },
                {
                    name: 'latest_submision.status',
                    label: 'Submission Status',
                    type: String,
                    sortable: true,
                    resolveValue (item) {
                        return item.group.latest_submission
                            ? item.group.latest_submission.status.name
                            : null
                    },
                    // resolveSort(item) {
                    //     return item.group.latest_submission
                    //         ? item.group.latest_submission.closed_at ? 'Closed' : 'Pending'
                    //         : <null></null>
                    // }
                },
                {
                    name: 'latest_log_entry',
                    label: 'Last Activity',
                    type: Date,
                    sortable: true,
                    resolveSort (item) {
                        if (item && item.group && item.group.latest_log_entry) {
                            return formatDate(item.group.latest_log_entry.created_at);
                        }
                        return null
                    },
                },
                {
                    name: 'next_actions',
                    label: 'Next Actions',
                    type: String,
                    sortable: false,
                    class: ['min-w-28', 'max-w-xs', 'truncate'],
                },
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
                        name: 'step_1_received_date',
                        label: this.epTypeId == 2 ? 'Step 1 Received' : 'Application Received',
                        type: Date,
                        sortable: true,
                        class: ['min-w-28'],
                        step: 1

                    },
                    {
                        name: 'step_1_approval_date',
                        label: this.epTypeId == 2 ? 'Step 1 Approved' : 'Application Approved',
                        type: Date,
                        sortable: true,
                        class: ['min-w-28'],
                        step: 1
                    },
                    {
                        name: 'step_2_approval_date',
                        label: 'Step 2 Approved',
                        type: Date,
                        sortable: true,
                        class: ['min-w-28'],
                        step: 2
                    },
                    {
                        name: 'step_3_approval_date',
                        label: 'Step 3 Approved',
                        type: Date,
                        sortable: true,
                        class: ['min-w-28'],
                        step: 3
                    },
                    {
                        name: 'step_4_received_date',
                        label: 'Step 4 Received',
                        type: Date,
                        sortable: true,
                        class: ['min-w-28'],
                        step: 4
                    },
                    {
                        name: 'step_4_approval_date',
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
                .filter(item => !this.epTypeId || item.expert_panel_type_id == this.epTypeId)
                .filter(item => {
                    if (!this.showCompleted) {
                        return item.date_completed == null;
                    }
                    return true;
                })

            if (this.waitingOn) {
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
            const params = {};
            const where = {
                expert_panel_type_id: this.epTypeId
            };

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
            this.$router.push({name: 'ApplicationDetail', params: {uuid: item.group.uuid}})
        },
    },
    mounted () {
        this.getApplications()
    },
    setup() {
        const {sort, filter} = sortAndFilter({
            field: 'full_name',
            desc: false
        });
        // const showAllInfo = computedQueryParam('showAllInfo');

        return {
            sort,
            filter,
            // showAllInfo
        }
    }
}
</script>
