<template>
    <div>
        <h1>Users</h1>
        <data-table 
            :fields="fields" 
            :data="filteredUsers" 
            v-model:sort="sort" 
            @rowClick="goToUser"
            row-class="cursor-pointer"
            paginated
        >
            <template v-slot:header>
                <div>Filter: &nbsp;<input type="text" v-model="filter" placeholder="name, email"></div>
            </template>
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
            if (!this.filter) {
                return this.users;
            }
            const pattern = new RegExp(`.*${this.filter}.*`, 'i')
            return this.users.filter(u => {
                return u.person.name.match(pattern)
                    || u.person.email.match(pattern);
            });
        }
    },
    watch: {
        filter: {
            immediate: true,
            handler () {
                this.currentPage = 0;
            }
        },
        sort: {
            immediate: true,
            handler () {
                this.currentPage = 0;
            }
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
            filter,
        }
    },
    mounted () {
        this.getUsers();
    }
}
</script>