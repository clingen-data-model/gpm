<script>
import GroupMember from '@/domain/group_member'
import {debounce} from 'lodash-es'

export default {
    name: 'ComponentName',
    props: {
        member: {
            type: GroupMember,
            required: true
        },
        readonly: {
            type: Boolean,
            required: false,
            default: false
        }
    },
    emits: [
        'updated'
    ],
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
        canEdit () {
            return this.hasAnyPermission([
                        'ep-applications-manage',
                        ['application-edit', this.group]
                    ])
                    && !this.readonly;
        }
    },
    watch: {
        member: {
            immediate: true,
            handler(to) {
                this.syncWorkingCopy(to);
            }
        }
    },
    created () {
        this.debounceSave = debounce(() => {
            this.save();
        }, 1000)
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

            promises.push(this.updateTrainingInfo());
            promises.push(this.syncRoles());

            return promises;
        },

        updateTrainingInfo () {
            if (this.workingCopy.isDirty('training_level_1') || this.workingCopy.isDirty('training_level_2')) {
                return this.$store.dispatch(
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
            }
        },

        syncRoles() {
            if (this.workingCopy.isDirty('roles')) {
                return this.$store.dispatch(
                        'groups/memberSyncRoles',
                        {
                            group: this.group,
                            member: this.workingCopy
                        }
                    );
            }
        },
        emitUpdated () {
            this.$emit('updated', this.workingCopy)
        }
    }
}
</script>
<template>
    <tr>
        <td>{{ workingCopy.person.first_name }}</td>
        <td>{{ workingCopy.person.last_name }}</td>
        <td colgroup="biocurator">
            <input v-model="biocurator" type="checkbox" :disabled="!canEdit" @input="debounceSave">
        </td>
        <td colgroup="biocurator">
            <input v-model="workingCopy.training_level_1" type="checkbox" :disabled="!canEdit" @input="debounceSave">
        </td>
        <td colgroup="biocurator">
            <input v-model="workingCopy.training_level_2" type="checkbox" :disabled="!canEdit" @input="debounceSave">
        </td>
        <td>
            <input v-model="biocuratorTrainer" type="checkbox" :disabled="!canEdit" @input="debounceSave">
        </td>
        <td>
            <input v-model="coreApprovalMember" type="checkbox" :disabled="!canEdit" @input="debounceSave">
        </td>
    </tr>
</template>
<style lang="postcss" scoped>
    td[colgroup=biocurator] {
        @apply bg-blue-50;
    }
    tr:nth-child(odd) > td[colgroup=biocurator] {
        @apply bg-blue-100;
    }
</style>
