<template>
    <div>
        <data-table 
            :data="groups" 
            :fields="fields" 
            v-model:sort="sort"
            :row-click-handler="goToGroup"
        ></data-table>
    </div>
</template>
<script>
// import { mapGetters } from 'vuex'
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
                    name: 'type.name',
                    label: 'Type',
                    sortable: true
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
        }
    }
    // computed: {
    //     ...mapGetters({
    //         groups: 'groups/all'
    //     }),
    //     filteredGroups () {
    //         return this.groups
    //     }
    // },
    // methods: {

    // },
    // mounted () {

    // }
}
</script>