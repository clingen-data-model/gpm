<template>
    <div>
        <h1>Users</h1>
        <data-table
            :fields="fields"
            :data="getUsers"
            v-model:sort="sort"
            @rowClick="goToUser"
            row-class="cursor-pointer"
            ref="dataTable"
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
import {debounce} from 'lodash-es'
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
                    name: 'roles',
                    label: 'System Roles',
                    type: String,
                    sortable: false,
                    resolveValue (item) {
                        return item.roles.map(r => r.display_name).join(', ')
                    }
                },
                {
                    name: 'permissions',
                    label: '+ Permissions',
                    type: String,
                    sortable: false,
                    resolveValue (item) {
                        return item.permissions.map(r => r.display_name).join(', ')
                    },
                    class: 'text-xs'
                },
                // {
                //     name: 'actions',
                //     sortable: false,
                //     label: ''
                // }

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
                if (this.triggerSearch) {
                    this.triggerSearch()
                }
            }
        },
        sort: {
            immediate: true,
            handler () {
                 if (this.triggerSearch) {
                    this.triggerSearch()
                }
            }
        }
    },
    methods: {
        async getUsers (currentPage, pageSize, sort, setTotalItems) {
            const params = {
                page: currentPage,
                page_size: pageSize,
                'sort[field]': sort.field.name,
                'sort[dir]': sort.desc ? 'DESC' : 'ASC',
                'where[filterString]': this.filter,
                'with': ['roles', 'permissions'],
                paginated: true
            }
            const pageResponse = await api.get('/api/users', {params: params})
                                .then(response => response.data);
            setTotalItems(pageResponse.meta.total);
            return pageResponse.data;
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
    created () {
        this.triggerSearch = debounce(() => this.$refs.dataTable.getItems(), 500)
    }
}
</script>
