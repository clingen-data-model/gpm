<template>
    <div>
        <data-table 
            :fields="fields" 
            :data="person.memberships" 
            v-model:sort="sort"
            :rowClickHandler="goToGroup"
            :row-class="() => 'cursor-pointer'"
        ></data-table>
    </div>
</template>
<script>
import Person from '@/domain/person'

export default {
    name: 'ComponentName',
    props: {
        person: {
            type: Person,
            required: true
        }
    },
    data() {
        return {
            sort: {
                field: 'group.name',
                desc: false
            },
            fields: [
                {
                    name: 'group.name',
                    type: String,
                    sortable: true,
                    label: 'Name'
                },
                {
                    name: 'group.type.name',
                    type: String,
                    sortable: true,
                    label: 'Type'
                },
                {
                    name: 'roles',
                    type: String,
                    sortable: false,
                    label: 'Roles',
                    resolveValue (item) {
                        return item.roles.map(r => r.name).join(', ');
                    }
                },
                {
                    name: 'permissions',
                    type: String,
                    sortable: false,
                    label: 'Permissions',
                    resolveValue (item) {
                        return item.permissions.map(p => p.name).join(', ');
                    }
                },
                {
                    name: 'start_date',
                    type: Date,
                    sortable: true,
                    label: 'Started'
                },
                {
                    name: 'end_date',
                    type: Date,
                    sortable: true,
                    label: 'Ended'
                }


            ]
        }
    },
    computed: {

    },
    methods: {
        goToGroup(item) {
            console.log(item.group.uuid);
            this.$router.push({name: 'GroupDetail', params: {uuid: item.group.uuid}})
        }

    }
}
</script>