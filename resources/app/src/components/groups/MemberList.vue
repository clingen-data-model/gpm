<script>
import config from '@/configs.json'
import { formatDate } from '@/date_utils'
import sortAndFilter from '@/composables/router_aware_sort_and_filter'
import ChevDownIcon from '@/components/icons/IconCheveronDown'
import ChevRightIcon from '@/components/icons/IconCheveronRight'
import FilterIcon from '@/components/icons/IconFilter'
import EditButton from '@/components/buttons/EditIconButton'
import { titleCase } from '@/utils'

export default {
    name: 'MemberList',
    components: {
        ChevRightIcon,
        ChevDownIcon,
        FilterIcon,
        EditButton,
    },
    props: {
        group: {
            type: Object,
            required: true
        }
    },
    data() {
        return {
            showFilter: false,
            filters: {
                keyword: null,
                roleId: null,
                needsCoi: null,
                needsTraining: null
            },
            tableFields: [
                {
                    name: 'id',
                    label: '',
                    type: Number,
                    sortable: false,
                },
                {
                    name: 'person.first_name',
                    label: 'First',
                    type: String,
                    sortable: true,
                },
                {
                    name: 'person.last_name',
                    label: 'Last',
                    type: String,
                    sortable: true,
                },
                {
                    name: 'person.timezone',
                    label: 'Closest City (for timezone)',
                    type: String,
                    sortable: true,
                },
                {
                    name: 'roles',
                    label: 'Roles',
                    sortable: false
                },
                {
                    name: 'coi_last_completed',
                    label: 'COI Completed',
                    type: Date,
                    sortable: true,
                },
                {
                    name: 'training_completed_at',
                    label: 'Training Completed',
                    type: Date,
                    sortable: true
                },
                {
                    name: 'actions',
                    label: '',
                    type: Object,
                    sortable: false
                }

            ]
        }
    },
    computed: {
        filteredMembers () {
            return (this.group.members) ? this.group.members.filter(m => this.matchesFilters(m)) : [];
        },
        roles () {
            return config.groups.roles;
        },
        coiCuttoff () {
            const cuttoff = new Date();
            cuttoff.setFullYear(cuttoff.getFullYear()-1);
            return cuttoff;
        }
    },
    setup() {
        const {sort, filter} = sortAndFilter({field: 'person.last_name', desc: false});

        return {
            sort,
            filter,
        }
    },
    methods: {
        logEvent(event) {
            console.log(event)
        },
        toggleFilter() {
            this.showFilter = !this.showFilter;
        },
        matchesFilters(member) {
            if (this.filters.keyword && !member.matchesKeyword(this.filters.keyword)) {
                return false;
            }
            if (this.filters.roleId && !member.hasRole(this.filters.roleId)) {
                return false;
            }
            if (this.filters.needsCoi && member.coiUpToDate()) {
                return false;
            }

            if (this.filters.needsTraining && member.trainingComplete()) {
                return false;
            }

            return true;
        },
        toggleItemDetails(item) {
            item.showDetails = !item.showDetails;
        },
        titleCase (string) {
            return titleCase(string);
        },
        formatDate (date) {
            return formatDate(date)
        },
        editMember (member) {
            this.$router.push({name: 'EditMember', params: {uuid: this.group.uuid, memberId: member.id}})
        }
    }
}
</script>
<template>
    <div>
        <head class="flex justify-between items-baseline">
            <div class="flex space-x-2 items-baseline">
                <h2>Members</h2>
                <button 
                    ref="filterToggleButton" 
                    class="px-3 py-2 rounded-t transition-color" 
                    :class="{'rounded-b': !showFilter, 'bg-blue-200': showFilter}" 
                    @click="toggleFilter">
                    <filter-icon></filter-icon>
                </button>
            </div>
            <router-link class="btn btn-xs" ref="addMemberButton" :to="{name: 'AddMember'}">Add Member</router-link>
        </head>
        <transition name="slide-fade-down">
            <div v-show="showFilter" class="flex justify-between px-2 space-x-2 bg-blue-200 rounded-lg">
                <div class="flex-1">
                    <input-row label="Keyword" type="text" v-model="filters.keyword" :label-width="20"></input-row>
                    <input-row label="Role" :label-width="20">
                    <select v-model="filters.roleId">
                        <option :value="null">Select&hellip;</option>
                        <option 
                            v-for="role in roles"
                            :key="role.id"
                            :value="role.id"
                        >
                            {{role.name}}
                        </option>
                    </select>
                </input-row>
                </div>
                <div class="flex-1 py-2">
                    <checkbox class="block" label="Needs COI" v-model="filters.needsCoi"></checkbox>
                    <checkbox class="block" label="Needs Training" v-model="filters.needsTraining"></checkbox>
                </div>
                <div class="flex-1">
                    <!-- bob -->
                </div>
            </div>
        </transition>
        
        <div class="mt-3">
            <data-table 
                :fields="tableFields" 
                :data="filteredMembers" 
                v-model:sort="sort" 
                :detailRows="true" 
                @update:sort="logEvent"
            >
                <template v-slot:cell-id="{item}">
                    <button @click="toggleItemDetails(item)" class="w-6">
                        <chev-right-icon v-if="!item.showDetails"></chev-right-icon>
                        <chev-down-icon v-if="item.showDetails"></chev-down-icon>
                    </button>
                </template>
                <template v-slot:cell-roles="{value}">
                    {{titleCase(value.map(i => i.name).join(', '))}}
                </template>
                <template v-slot:cell-coi_last_completed="{value}">
                    <div>
                        <span v-if="value">{{formatDate(value)}}</span>
                    </div>
                </template>
                <template v-slot:cell-training_completed_at="{item, value}">
                    <div>
                        <span v-if="value">{{formatDate(value)}}</span>
                        <span v-if="!item.needs_training" class="text-gray-500">N/A</span>
                    </div>
                </template>
                <template v-slot:cell-actions="{item}">
                    <edit-button @click="editMember(item)"></edit-button>
                </template>

                <template v-slot:detail="{item}">
                    <div class="px-8 py-2 inset">
                        <object-dictionary 
                            :obj="item.person.attributes" 
                            :only="['first_name', 'last_name','email']"
                        ></object-dictionary>
                        <dictionary-row label="Member ID">{{item.id}}</dictionary-row>
                    </div>
                </template>
            </data-table>
        </div>
        <dev-todo :items="[
            '~ Store filter state in url.',
        ]" class="mt-4"></dev-todo>
    </div>
</template>
<style>
    .inset {
        box-shadow: 
            inset 0px 11px 15px -10px #CCC,
            inset 0px -11px 10px -10px #EEE; 
    }
    .transition-color {
        transition: background-color .3 linear;
    }
</style>