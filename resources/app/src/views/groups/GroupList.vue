<template>
    <div>
        <h1 class="flex justify-between items-center">
            Groups
            <button v-if="hasPermission('groups-manage')" class="btn btn-xs" @click="startCreateGroup">Create a group</button>
        </h1>
        <data-table 
            :data="groups" 
            :fields="fields" 
            v-model:sort="sort"
            :row-click-handler="goToGroup"
            v-remaining-height
        ></data-table>
    </div>
</template>
<script>
import {useStore} from 'vuex'
import {useRouter} from 'vue-router'
import {computed, onMounted } from 'vue'

export default {
    name: 'ComponentName',
    props: {
        
    },
    data() {
        return {
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
                    sortable: true
                },
                {
                    name: 'type',
                    label: 'Type',
                    sortable: true,
                    resolveSort: (group) => {
                        if (group.isEp()) {
                            return group.expert_panel.type.name;
                        }
                        return group.type.name;
                    },
                    resolveValue: (group) => {
                        if (group.isEp()) {
                            return group.expert_panel.type.name.toUpperCase();
                        }

                        return group.type.name.toUpperCase();
                    }
                }
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