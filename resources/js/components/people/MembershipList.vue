<script>
import {Group} from '@/domain'

export default {
    name: 'MembershipList',
    props: {
        person: {
            type: Object,
            required: true
        }
    },
    data() {
        return {
            sort: {
                field: 'group.displayName',
                desc: false
            },
            fields: [
                {
                    name: 'group.displayName',
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
        memberships () {
            if (this.person.memberships) {
                return this.person.memberships.map(m => {
                    if (m.group) {
                        m.group = new Group(m.group);
                    }
                    return m;
                })
            }

            return [];
        }
    },
    methods: {
        goToGroup(item) {
            this.$router.push({name: 'GroupDetail', params: {uuid: item.group.uuid}})
        }

    }
}
</script>
<template>
  <div>
    <data-table 
      v-model:sort="sort" 
      :fields="fields" 
      :data="memberships"
      :row-click-handler="goToGroup"
      :row-class="() => 'cursor-pointer'"
    />
  </div>
</template>