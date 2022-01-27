<template>
    <div>
        <h1>Users</h1>
        <div class="flex justify-between mb-2">
            <div class="flex space-x-2 items-center">
                Filter: &nbsp;<input type="text" v-model="filter" placeholder="name, email">
            </div>
        </div>
        <data-table 
            :fields="fields" 
            :data="filteredUsers" 
            v-model:sort="sort" 
            @rowClick="goToUser"
            row-class="cursor-pointer"
        >
            <!-- <template v-slot:cell-actions="{item}">
                <router-link :to="`/users/${item.id}`">Edit</router-link>
            </template> -->
        </data-table>
    </div>
</template>
<script>
import {api} from '@/http'
import sortAndFilterSetup from '@/composables/router_aware_sort_and_filter'

export default {
    name: 'ComponentName',
    props: {
        
    },
    data() {
        return {
            users: [],
            fields: [
                {
                    name: 'id',
                    type: Number,
                    sortable: true
                },
                {
                    name: 'person.name',
                    label: 'Name',
                    type: String,
                    sortable: true
                },
                {
                    name: 'person.email',
                    label: 'Email',
                    type: String,
                    sortable: true
                },
                {
                    name: 'actions',
                    sortable: false,
                    label: ''
                }
                
            ]
        }
    },
    computed: {
        filteredUsers () {
            return this.users.filter(() => true);
        }
    },
    methods: {
        async getUsers () {
            this.users = await api.get('/api/users')
                                .then(response => response.data);
        },
        goToUser (user) {
            this.$router.push({name: 'UserDetail', params: {id: user.id}})
        }
    },
    setup() {
        const {sort, filter} = sortAndFilterSetup({
            field: 'person.name',
            desc: false
        });

        return {
            sort,
            filter
        }
    },
    mounted () {
        this.getUsers();
    }
}
</script>