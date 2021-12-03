<style lang="postcss" scoped>
    td[colgroup=biocurator] {
        @apply bg-blue-50
    }
    tr:nth-child(odd) > td[colgroup=biocurator] {
        @apply bg-blue-100;
    }
</style>
<template>
    <tr>
        <td>{{workingCopy.person.first_name}}</td>
        <td>{{workingCopy.person.last_name}}</td>
        <td colgroup="biocurator">
            <input type="checkbox" v-model="biocurator">
        </td>
        <td colgroup="biocurator">
            <input type="checkbox" v-model="workingCopy.training_level_1">
        </td>
        <td colgroup="biocurator">
            <input type="checkbox" v-model="workingCopy.training_level_2">
        </td>
        <td>
            <input type="checkbox" v-model="biocuratorTrainer">
        </td>
        <td>
            <input type="checkbox" v-model="coreApprovalMember">
        </td>
        <!-- <td>{{workingCopy.original.roles}}</td> -->
    </tr>
</template>
<script>
import GroupMember from '@/domain/group_member'
import configs from '@/configs'

const roles = configs.groups.roles;

export default {
    name: 'ComponentName',
    props: {
        member: {
            type: GroupMember,
            required: true
        }
    },
    data() {
        return {
            workingCopy: new GroupMember(),
        }
    },
    computed: {
        group () {
            return this.$store.getters['groups/currentItemOrNew'];
        },
        biocurator: {
            get () {
                return this.workingCopy.hasRole('biocurator');
            },
            set (value) {
                this.toggleRole(value, 'biocurator')
            }
        },
        biocuratorTrainer: {
            get () {
                return this.workingCopy.hasRole('biocurator-trainer')
            },
            set (value) {
                this.toggleRole(value, 'biocurator-trainer')
            }
        },
        coreApprovalMember: {
            get () {
                return this.workingCopy.hasRole('core-approval-member')
            },
            set (value) {
                this.toggleRole(value, 'core-approval-member')
            }
        },
        
    },
    watch: {
        member: {
            immediate: true,
            handler(to) {
                this.syncWorkingCopy(to);
            }
        }
    },
    methods: {
        initWorkingCopy() {
            this.workingCopy = new GroupMember();
        },
        syncWorkingCopy (member) {
            if (member.id) {
                this.workingCopy = member.clone();
            }
        },

        toggleRole (hasRole, roleName) {
            if (hasRole) {
                this.workingCopy.addRole(roleName);
                return;
            }

            this.workingCopy.removeRole(roleName);
        },
        save () {
            const promises = [];
            if (this.workingCopy.isDirty('training_level_1') || this.workingCopy.isDirty('training_level_2')) {
                console.log(`Update training info for ${this.workingCopy.person.name}`)
                promises.push(
                    this.$store.dispatch(
                        'groups/memberUpdate', 
                        {
                            groupUuid: this.group.uuid, 
                            memberId: this.workingCopy.id, 
                            data: {
                                training_level_1: this.workingCopy.training_level_1,
                                training_level_2: this.workingCopy.training_level_2,
                            }
                        }
                    )
                )
            }

            if (this.workingCopy.isDirty('roles')) {
                console.log('sync roles!')
                promises.push(this.syncRoles());
            }
            return promises;
        },
        syncRoles() {
            const promises = [];
            const newRoleIds = this.workingCopy.addedRoles.map(r => r.id);
            const removedRoleIds = this.workingCopy.removedRoles.map(r => r.id);
            
            if (newRoleIds.length > 0) {
                promises.push(this.$store.dispatch(
                    'groups/memberAssignRole', 
                    {uuid: this.group.uuid, memberId: this.workingCopy.id, roleIds: newRoleIds}
                ));
            }
            
            removedRoleIds.forEach(roleId => {
                promises.push(this.$store.dispatch(
                    'groups/memberRemoveRole', 
                    {uuid: this.group.uuid, memberId: this.workingCopy.id, roleId: roleId}
                ));
            });

            return promises;
        },

    }
}
</script>