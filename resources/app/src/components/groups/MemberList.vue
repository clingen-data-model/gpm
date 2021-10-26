<script>
import configs from '@/configs.json'
import { formatDate, yearAgo } from '@/date_utils'
import sortAndFilter from '@/composables/router_aware_sort_and_filter'
import ChevDownIcon from '@/components/icons/IconCheveronDown'
import ChevRightIcon from '@/components/icons/IconCheveronRight'
import FilterIcon from '@/components/icons/IconFilter'
import ExclamationIcon from '@/components/icons/IconExclamation'
import NotificationIcon from '@/components/icons/IconNotification'
import DropdownMenu from '@/components/DropdownMenu'
import DropdownItem from '@/components/DropdownItem'
import MemberPreview from '@/components/groups/MemberPreview'
import { titleCase } from '@/utils'

export default {
    name: 'MemberList',
    components: {
        ChevRightIcon,
        ChevDownIcon,
        FilterIcon,
        DropdownMenu,
        DropdownItem,
        MemberPreview,
        ExclamationIcon,
        NotificationIcon,
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
            showConfirmRetire: false,
            showConfirmRemove: false,
            filters: {
                keyword: null,
                roleId: null,
                needsCoi: null,
                needsTraining: null,
                hideAlumns: true 
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
                // {
                //     name: 'training_completed_at',
                //     label: 'Training Completed',
                //     type: Date,
                //     sortable: true
                // },
                {
                    name: 'actions',
                    label: '',
                    type: Object,
                    sortable: false
                }

            ],
            selectedMember: null,
            hideAlumns: true
        }
    },
    computed: {
        filteredMembers () {
            if (!this.group.members) {
                return [];
            }
            return this.group.members
                    .filter(m => this.matchesFilters(m))
                    .filter(m => {
                        if (this.filters.hideAlumns) {
                            return m.end_date === null;
                        }
                        return m;
                    });
        },
        fieldsForGroupType () {
            const fields = [...this.tableFields];
            if (!this.group.isEp()) {
               delete fields.splice(fields.findIndex(f => f.name == 'coi_last_completed'), 1);
            }
            return fields;
        },
        roles () {
            return configs.groups.roles;
        },
        coiCuttoff () {
            const cuttoff = new Date();
            cuttoff.setFullYear(cuttoff.getFullYear()-1);
            return cuttoff;
        },
        selectedMemberName () {
            return this.selectedMember ? this.selectedMember.person.name : null
        }
    },
    setup() {
        const {sort, filter} = sortAndFilter({field: 'person.last_name', desc: false});

        return {
            sort,
            filter,
            yearAgo
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
        },
        confirmRetireMember (member) {
            this.showConfirmRetire = true;
            this.selectedMember = member;
            console.log(this.selectedMember)
        },
        async retireMember (member) {
            try {
                await this.$store.dispatch('groups/memberRetire', {
                    uuid: this.group.uuid,
                    memberId: member.id,
                    startDate: member.start_date, 
                    endDate: new Date().toISOString()
                });
                this.cancelRetire();
            } catch (error) {
                console.error(error);
            }
        },
        cancelRetire () {
            this.selectedMember = null;
            this.showConfirmRetire = false;
        },
        confirmRemoveMember (member) {
            this.showConfirmRemove = true;
            this.selectedMember = member;
        },
        async removeMember () {
            try {
                await this.$store.dispatch('groups/memberRemove', {
                    uuid: this.group.uuid,
                    memberId: this.selectedMember.id,
                    startDate: this.selectedMember.start_date, 
                    endDate: new Date().toISOString()
                });
                this.cancelRemove();
            } catch (error) {
                console.error(error);
            }
        },
        cancelRemove () {
            this.selectedMember = null;
            this.showConfirmRemove = false;
        },
        goToMember (member) {
            this.$router.push({name: 'PersonDetail', params: {uuid: member.person.uuid}})
        },
        hasAnyMemberPermission () {
            const hasPerm = this.hasAnyPermission([
                ['members-update', this.group],
                ['members-retire', this.group],
                ['members-remove', this.group],
                'groups-manage'
            ])

            console.log(hasPerm);
            return hasPerm;
        },
        getCoiDateStyle (member) {
            if (member.coi_last_completed === null) {
                return 'text-red-700';
            }
            return 'text-yellow-500';
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
                    @click="toggleFilter"
                    v-if="group.members.length > 0"
                >
                    <filter-icon></filter-icon>
                </button>
            </div>
            <router-link 
                class="btn btn-xs" 
                ref="addMemberButton" 
                :to="{name: 'AddMember'}" 
                v-if="hasAnyPermission([['members-invite', group], 'groups-manage'])"
            >Add Member</router-link>
        </head>
        <transition name="slide-fade-down">
            <div v-show="showFilter" class="flex justify-between px-2 space-x-2 bg-blue-200 rounded-lg"                v-if="group.members.length > 0">
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
                    <!-- <checkbox class="block" label="Needs Training" v-model="filters.needsTraining"></checkbox> -->
                </div>
                <div class="flex-1 py-2">
                    <checkbox class="block" label="Hide Alumns" v-model="filters.hideAlumns"></checkbox>
                </div>
            </div>
        </transition>
        
        <div class="mt-3">
            <data-table 
                :fields="fieldsForGroupType" 
                :data="filteredMembers" 
                v-model:sort="sort" 
                :detailRows="true" 
                @update:sort="logEvent"
                :row-class="(item) => 'cursor-pointer'+ (item.isRetired ? ' retired-member' : '')"
                @rowClick="goToMember"
                v-if="group.members.length > 0"
            >
                <template v-slot:cell-id="{item}">
                    <button @click.stop="toggleItemDetails(item)" class="w-9 align-center block -mx-3">
                        <chev-right-icon v-if="!item.showDetails" class="m-auto cursor-pointer"></chev-right-icon>
                        <chev-down-icon v-if="item.showDetails" class="m-auto cursor-pointer"></chev-down-icon>
                    </button>
                </template>
                <template v-slot:cell-roles="{value}">
                    {{titleCase(value.map(i => i.name).join(', '))}}
                </template>
                <template v-slot:cell-coi_last_completed="{item}">
                    <div class="flex space-x-2">
                        <span v-if="item.coi_last_completed">{{formatDate(item.coi_last_completed)}}</span>
                        <exclamation-icon 
                            v-if="!item.coi_last_completed === null || (item.coi_last_completed < yearAgo())"
                            :class="getCoiDateStyle(item)"
                        ></exclamation-icon>
                    </div>
                </template>
                <!-- <template v-slot:cell-training_completed_at="{item, value}">
                    <div>
                        <span v-if="value">{{formatDate(value)}}</span>
                        <span v-if="!item.needs_training" class="text-gray-500">N/A</span>
                    </div>
                </template> -->
                <template v-slot:cell-actions="{item}">
                    <div class="flex space-x-2">
                        <dropdown-menu :hide-cheveron="true" class="relative" v-if="hasAnyMemberPermission()">
                            <template v-slot:label>
                                <button class="btn btn-xs">&hellip;</button>
                            </template>
                            <dropdown-item
                                v-if="hasAnyPermission([['members-update', group], 'groups-manage'])"
                            >
                                <div @click="editMember(item)">Update membership</div>
                            </dropdown-item>
                            <dropdown-item
                                v-if="hasAnyPermission([['members-retire', group], 'groups-manage'])"
                            >
                                <div @click="confirmRetireMember(item)">Retire from group</div>
                            </dropdown-item>
                            <dropdown-item
                                v-if="hasAnyPermission([['members-remove', group], 'groups-manage'])"
                            >
                                <div @click="confirmRemoveMember(item)">Remove from group</div>
                            </dropdown-item>
                        </dropdown-menu>
                        <notification-icon v-if="item.is_contact" :width="12" :height="12" icon-name="Is a group contact"></notification-icon>
                    </div>
                </template>

                <template v-slot:detail="{item}">
                    <member-preview :member="item" :group="group"></member-preview>
                </template>
            </data-table>
            <div v-else class="well">
                This group does not yet have any members.
            </div>
        </div>
        <!-- <dev-todo :items="[
            '~ Store filter state in url.',
        ]" class="mt-4"></dev-todo> -->
        <teleport to='body'>
            <modal-dialog v-model="showConfirmRetire" size="xs" :title="`Retire ${selectedMemberName}?`">
                <!-- <h3>Retire {{selectedMemberName}}?</h3> -->
                <p class="text-lg">
                    Are you sure you want to retire {{selectedMemberName}} from this group?
                </p>

                <button-row @submit="retireMember" @cancel="cancelRetire" submit-text="Retire Member"></button-row>
            </modal-dialog>
            <modal-dialog v-model="showConfirmRemove" size="xs" :title="`Remove ${selectedMemberName}?`">
                <p class="text-lg"> Are you sure you want to remove {{selectedMemberName}} from this group?</p>
                <p><strong>This cannot be undone.</strong></p>
                <button-row @submit="removeMember" @cancel="cancelRemove" submit-text="Remove Member"></button-row>
            </modal-dialog>
        </teleport>
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
    .retired-member > td {
        @apply text-gray-400;
    }
</style>