<template>
    <div>
        <h1 class="flex justify-between items-center">
            Groups
            <button v-if="hasPermission('groups-manage')" class="btn btn-xs" @click="startCreateGroup">Create a group</button>
        </h1>
        <tabs-container>
            <tab-item v-for="def in tabDefinitions" :label="def.label" :key="def.label">
                <data-table 
                    :data="filteredGroups.filter(def.filter)" 
                    :fields="fields" 
                    v-model:sort="sort"
                    :row-click-handler="goToGroup"
                    v-remaining-height
                    row-class="cursor-pointer active:bg-blue-100"
                >
                    <template v-slot:cell-displayStatus="{item}">
                        <badge class="text-xs" :color="item.statusColor">{{item.displayStatus}}</badge>
                    </template>
                    <template v-slot:cell-coordinators="{value}">
                        <div v-if="value.length == 0"></div>
                        <span v-for="(coordinator, idx) in value" :key="coordinator.id">
                            <span v-if="idx > 0">, </span>
                            <router-link 
                                :to="{name: 'PersonDetail', params: {uuid: coordinator.person.uuid}}" 
                                class="link"
                                @click.stop
                            >
                                {{coordinator.person.name}}
                            </router-link>
                        </span>
                    </template>
                </data-table>                
            </tab-item>
        </tabs-container>

        <modal-dialog v-model="showCreateForm" title="Create a New Group" size="sm">
            <submission-wrapper @submitted="$refs.groupForm.save()" @canceled="$refs.groupForm.cancel()">
                <group-form ref='groupForm' @canceled="showCreateForm=false" @saved="showCreateForm = false"></group-form>
            </submission-wrapper>
        </modal-dialog>
    </div>
</template>
<script>
import {useStore} from 'vuex'
import {useRouter} from 'vue-router'
import {computed, onMounted } from 'vue'
import GroupForm from '@/components/groups/GroupForm'
import SubmissionWrapper from '@/components/groups/SubmissionWrapper'

export default {
    name: 'ComponentName',
    components: {
        GroupForm,
        SubmissionWrapper
    },
    props: {
        
    },
    data() {
        return {
            showCreateForm: false,
            tabDefinitions:[
                {
                    label: 'VCEPs',
                    filter: g => g.isVcep()
                },
                {
                    label: 'GCEPs',
                    filter: g => g.isGcep()
                },
                {
                    label: 'CDWGs',
                    filter: g => g.isCdwg()
                },
                {
                    label: 'WGs',
                    filter: g => g.isWg()
                }
            ],
            sort: {
                field: 'id',
                desc: false
            },
            fields: [
                {
                    name: 'id',
                    label: 'ID',
                    sortable: true
                },
                {
                    name: 'name',
                    label: 'Name',
                    sortable: true,
                    resolveValue: (item) => {
                        return item.displayName
                    }
                },
                {
                    name: 'coordinators',
                    sortable: false
                },
                {
                    name: 'displayStatus',
                    sortable: true,
                    label: 'status'
                },
            ]
        }
    },
    methods: {
        startCreateGroup () {
            this.showCreateForm = true;
        }
    },
    setup() {
        const store = useStore();
        const router = useRouter();

        let groups = computed(() => store.getters['groups/all']);
        let filteredGroups = computed(() => groups.value.filter(() => true))
        const goToItem = (item) => {
            router.push({
                name: 'GroupDetail',
                params: {uuid: item.uuid}
            })
        }

        onMounted(() => {
            store.dispatch('groups/getItems')
        })
        
        return {
            groups,
            filteredGroups,
            goToItem,
            goToGroup: goToItem,
        }
    }

}
</script>