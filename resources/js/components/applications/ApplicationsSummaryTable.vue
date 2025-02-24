<script>
import sortAndFilter from '@/composables/router_aware_sort_and_filter'
import { mapGetters } from 'vuex'

export default {
    name: 'SummaryVceps',
    components: {
    },
    props: {
        expertPanelTypeId: {
            type: Number,
            default: null
        }
    },
    setup() {
        const {sort, filter} = sortAndFilter();

        return {
            sort,
            filter,
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
                    name: 'group.parent.name',
                    label: 'CDWG',
                    type: String,
                    sortable: true,
                    resolveValue (item) {
                        if (item.group && item.group.parent) {
                            return item.group.parent.name
                        }
                        return '';
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
                    name: 'step_1_received_date',
                    label: this.expert_panel_type_id === 2 ? 'Step 1 Received' : 'Application Received',
                    type: Date,
                    sortable: true,
                    class: ['min-w-28'],
                    step: 1

                },
                {
                    name: 'step_1_approval_date',
                    label: this.expert_panel_type_id === 2 ? 'Step 1 Approved' : 'Application Approved',
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
            ]
        }
    },
    computed: {
        ...mapGetters({
            applications: 'applications/all'
        }),
        filteredData() {
            return this.applications
                .filter(item => !this.expert_panel_type_id || item.expert_panel_type_id === this.expert_panel_type_id)
                .filter(item => {
                    if (!this.showCompleted) {
                        return item.date_completed == null;
                    }
                    return true;
                })
        },
        showCompleted: {
            set(value) {
                const currentQuery = this.$route.query;
                const currentPath = this.$route.path;
                
                let updatedQuery = {...currentQuery};

                if (!value) {
                    delete updatedQuery['show-completed'];
                } else {
                    updatedQuery = {...currentQuery, ...{'show-completed': 1} };
                }

                this.$router.replace({path: currentPath, query: updatedQuery})
            },
            get() {
                return Boolean(Number.parseInt(this.$route.query['show-completed']))
            },
            immediate: true
        },
        selectedFields() {
            const stepsToShow = this.expert_panel_type_id === 2 ? [1,2,3,4] : [1]
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
    mounted () {
        this.getApplications()
    },
    methods: {

        getApplications () {
            const params = {
                with: [
                    'group',
                    'group.parent',
                    'group.latestLogEntry',
                    'latestPendingNextAction',
                    'type',
                    'group.contacts',
                    'group.contacts.person',
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
}
</script>
<template>
  <div>
    <div class="flex justify-between">
      <div class="mb-1 flex space-x-2">
        <label>Filter: <input v-model="filter" type="text" class="sm" placeholder="filter"></label>
        <checkbox 
          v-model="showCompleted"
          label="Show completed"
        />
      </div>
    </div>
    <data-table 
      v-model:sort="sort" 
      :data="filteredData" 
      :fields="selectedFields" 
      :filter-term="filter"
      :row-click-handler="goToApplication"
      row-class="cursor-pointer"
      :style="remainingHeight"
      class="overflow-auto text-xs"
    >
      <template #cell-contacts="{item}">
        <ul>
          <li v-for="member in item.group.contacts" :key="member.id">
            <small><a :href="`mailto:${member.person.email}`" class="text-blue-500">{{ member.person.name }}</a></small>
          </li>
        </ul>
      </template>
      <template #cell-latest_log_entry_description="{value}">
        <div v-html="value" />
      </template>
      <template #cell-latest_pending_next_action_entry="{value}">
        <div v-html="value" />
      </template>
    </data-table>
  </div>
</template>