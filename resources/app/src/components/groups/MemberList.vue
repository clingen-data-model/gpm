<script>
import config from '@/configs.json'
import ChevRightIcon from '@/components/icons/IconCheveronRight'
import ChevDownIcon from '@/components/icons/IconCheveronDown'
import { titleCase } from '@/utils'
import { formatDate } from '@/date_utils'

export default {
    name: 'MemberList',
    components: {
        ChevRightIcon,
        ChevDownIcon,
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
            tableSort: {
                field: 'person.last_name',
                desc: false,
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
                }

            ]
        }
    },
    computed: {
        filteredMembers () {
            // return this.group.members.filter(m => this.matchesFilters(m));
            return this.group.members;
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
    methods: {
        toggleFilter() {
            this.showFilter = !this.showFilter;
        },
        matchesFilters(member) {
            // implement
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
        sendCoiReminder (member) {

        },
        sendTrainingReminder (member) {

        }
    }
}
</script>
<template>
    <div>
        <div class="border border-pink-500 bg-pink-50 my-4 text-pink-700 py-4">
            <ol class="list-decimal pl-8">
                <li>Fix table sorting &amp; store state in url.</li>
                <li>Implement filtering &amp; store state in url.</li>
                <li>Get filter icon in filter button</li>
                <li>Implement Add Member form</li>
                <li>Implement Role &amp; permission editing</li>
            </ol>
        </div>
        <head class="flex justify-between items-baseline">
            <div class="flex space-x-2 items-baseline">
                <h2>Members</h2>
                <button ref="filterToggleButton" class="px-3 py-1 bg-blue-200 rounded-t" :class="{'rounded-b': !showFilter}" @click="toggleFilter">F</button>
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
                    <checkbox class="block" v-model="filters.needsCoi">Needs COI</checkbox>
                    <checkbox class="block" v-model="filters.needsTraining">Needs Training</checkbox>
                </div>
                <div class="flex-1">
                    <!-- bob -->
                </div>
            </div>
        </transition>
        
        <div class="mt-3">
            <data-table :fields="tableFields" :data="filteredMembers" :sort="tableSort" :detailRows="true">
                <template v-slot:cell-id="{item}">
                    <button @click="toggleItemDetails(item)">
                        <chev-right-icon v-if="!item.showDetails"></chev-right-icon>
                        <chev-down-icon v-if="item.showDetails"></chev-down-icon>
                    </button>
                </template>
                <template v-slot:cell-roles="{value}">
                    {{titleCase(value.map(i => i.name).join(', '))}}
                </template>
                <template v-slot:cell-coi_last_completed="{item, value}">
                    <div>
                        <span v-if="value">{{formatDate(value)}}</span>
                        <span v-if="!value || value < Date.parse(coiCuttoff)">
                            <span v-if="value">&nbsp;</span>
                            <button class="link text-blue-500 underline" @click="sendCoiReminder(item)">Remind</button>
                        </span>
                    </div>
                </template>
                <template v-slot:cell-training_completed_at="{item, value}">
                    <div>
                        <span v-if="value">{{formatDate(value)}}</span>
                        <span v-if="!item.needs_training" class="text-gray-500">N/A</span>
                        <span v-if="!value && item.needs_training">
                            <span v-if="value">&nbsp;</span>
                            <button class="link text-blue-500 underline" @click="sendTrainingReminder(item)">Remind</button>
                        </span>
                    </div>
                </template>

                <template v-slot:detail="{item}">
                    <div class="px-8 py-2 inset">
                        <object-dictionary :obj="item.person"></object-dictionary>
                    </div>
                </template>
            </data-table>
        </div>
    </div>
</template>
<style>
    .inset {
        box-shadow: 
            inset 0px 11px 15px -10px #CCC,
            inset 0px -11px 10px -10px #EEE; 
    }
</style>