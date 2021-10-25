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
                        <badge class="text-xs" :color="statusColors[item.status.id]">{{item.displayStatus}}</badge>
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
    </div>
</template>
<script>
import {useStore} from 'vuex'
import {useRouter} from 'vue-router'
import {computed, onMounted } from 'vue'
import configs from '@/configs'

console.log(configs.groups)

export default {
    name: 'ComponentName',
    props: {
        
    },
    data() {
        return {
            statusColors: configs.groups.statusColors,
            tabDefinitions:[
                {
                    label: 'VCEPS',
                    filter: g => g.isVcep()
                },
                {
                    label: 'GCEPS',
                    filter: g => g.isGcep()
                },
                {
                    label: 'CDWGs',
                    filter: g => g.isCdwg()
                },
                // {
                //     label: 'Working Groups',
                //     filter: g => g.isWg()
                // }
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
                // {
                //     name: 'type',
                //     label: 'Type',
                //     sortable: true,
                //     resolveSort: (group) => {
                //         if (group.isEp()) {
                //             return group.expert_panel.type.name;
                //         }
                //         return group.type.name;
                //     },
                //     resolveValue: (group) => {
                //         if (group.isEp()) {
                //             return group.expert_panel.type.name.toUpperCase();
                //         }

                //         return group.type.name.toUpperCase();
                //     }
                // }
            ]
        }
    },
    setup() {
        const store = useStore();
        const router = useRouter();
        // console.log(store);
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
            startCreateGroup: () => { console.log('start creating a group here!')}
        }
    }

}
</script>