<template>
    <div>
        <div class="flex">
            <div class="flex-1">
                <div v-if="!newMember.id && !newMember.person_id">
                    <input-row label="Name">
                        <div class="flex space-x-2">
                            <input type="text" 
                                v-model="newMember.person.first_name" 
                                placeholder="First" 
                                class="block w-1/2"
                                @input="getSuggestedPeople"
                            >
                            <input type="text" 
                                v-model="newMember.person.last_name" 
                                placeholder="Last" 
                                class="block w-1/2"
                                @input="getSuggestedPeople"
                            >
                        </div>
                    </input-row>
                    <input-row label="Email" 
                        v-model="newMember.person.email" 
                        placeholder="example@example.com" 
                        input-class="w-full"
                        @input="getSuggestedPeople"
                    ></input-row>
                </div>
                <div v-if="newMember.id || newMember.person_id">
                    <dictionary-row label="Name">
                        {{newMember.person.first_name}}
                        {{newMember.person.last_name}}
                    </dictionary-row>
                    <dictionary-row label="Email">{{newMember.person.email}}</dictionary-row>
                    <static-alert v-if="!newMember.id">
                        Adding existing person, {{newMember.person.name}}, as a group member.
                    </static-alert>
                </div>
                
                <div class="border-t mt-4 pt-2">
                    <h3>Group Roles</h3>
                    <div class="flex flex-col h-24 flex-wrap">

                        <label v-for="role in roles" :key="role.id">
                            <input type="checkbox" v-model="newMember.roles" :value="role">
                            {{role.name}}
                        </label>
                    </div>
                </div>

                <div class="border-t mt-4 pt-2">
                    <h3>Group Permissions</h3>
                    <div class="flex flex-col h-24 flex-wrap">
                        <div 
                            v-for="permission in permissions" 
                            :key="permission.id"
                        >
                            <input type="checkbox"
                                v-model="newMember.permissions" 
                                :value="permission"
                                v-if="!newMember.hasPermissionThroughRole(permission)"
                                :id="`permission-${permission.id}`"
                            >
                            <input type="checkbox"
                                :value="permission"
                                v-else
                                :checked="true"
                                :disabled="true"
                                style="background-color: red"
                                :id="`permission-${permission.id}`"
                                title="granted with role"
                            >
                            <label :for="`permission-${permission.id}`" title="granted with role">&nbsp;{{permission.name}}</label>
                        </div>
                    </div>
                    <div class="px-2 py-1 bg-gray-100 border relative text-xs">
                        <div class="flex space-x-2">
                        <strong>Legend: </strong>
                            <div><input type="checkbox">&nbsp;<label>Not granted</label></div>
                            <div><input type="checkbox" checked>&nbsp;<label>Granted</label></div>
                            <div><input type="checkbox" checked disabled>&nbsp;<label>Granted w/ role</label></div>
                        </div>
                        <div class="absolute top-0 left-0 w-full h-full bg-pink-500 opacity-0">&nbsp;</div>
                    </div>
                </div>

            </div>
            <transition name="slide-fade">            
                <div class="pt-2 border-l pl-2 flex-1" v-if="suggestedPeople.length > 0 && newMember.person_id === null">
                    <h5 class="font-bold border-b mb-1 pb-1">Matching people</h5>
                    <member-suggestions 
                        :suggestions="suggestedPeople"
                        @selected="useExistingPerson"
                    ></member-suggestions>
                </div>
            </transition>
        </div>
        <button-row
            @submit="save"
            @cancel="cancel"
            submit-text="Save"
        ></button-row>
        <dev-todo class="mt-8" :items="['display validation errors.']"></dev-todo>
    </div>
</template>
<script>
import {groups} from '@/configs'
import {computed} from 'vue'
import { useStore } from 'vuex'
import GroupMember from '@/domain/group_member'
import MemberSuggestions from '@/components/groups/MemberSuggestions'
import is_validation_error from '@/http/is_validation_error'

export default {
    name: 'AddMemberForm',
    components: {
        MemberSuggestions
    },
    props: {
        uuid: {
            required: true,
            type: String
        },
        memberId: {
            required: false,
            default: null
        }
    },
    emits: [
        'saved',
        'canceled',
        'closed'
    ],
    data () {
        return {
            newMember: new GroupMember(),
            errors: {},
            suggestedPeople: []
        }
    },
    setup () {
        const store = useStore();
        let group = computed(() => store.getters['groups/currentItem'] || {});
        let people = computed(() => store.getters['people/all'] || {});
        const roles = groups.roles;
        const permissions = groups.permissions;

        return {    
            group,
            people,
            roles,
            permissions,
        }
    },
    watch: {
        group () {
            this.syncMember();
        }
    },
    methods: {
        getSuggestedPeople() {
            let suggestedPeople = this.people.filter(p => {
                                        return p.matchesKeyword(this.newMember.person.first_name)
                                                || p.matchesKeyword(this.newMember.person.last_name)
                                                || p.matchesKeyword(this.newMember.person.email)
                                    }).filter(p => {
                                        return !this.isAlreadyMember(p)
                                    });

            this.suggestedPeople = Array.from(new Set(suggestedPeople));
        },
        initNewMember() {
            this.newMember = new GroupMember();
        },
        syncMember () {
            try {
                if (this.memberId) {
                    this.newMember = this.group.findMember(this.memberId).clone();
                }
            } catch (error) {
                if (this.group.isPersisted()) {
                    alert(error);
                }
            }
        },
        clearForm () {
            this.initNewMember();
        },
        cancel () {
            this.clearForm();
            this.$emit('canceled');
        },
        async save () {
            if (!this.newMember.isPersisted()) {
                if (!this.newMember.person.isPersisted()) {
                    await this.inviteNewMember(this.group, this.newMember);
                }
                if (this.newMember.person.isPersisted()) {
                    await this.addPersonAsMember(this.group, this.newMember);
                }
            }
            if (this.newMember.isPersisted()) {
                await this.updateExistingMember(this.group, this.newMember);
            }
            this.clearForm();
            this.$emit('saved');
        },
        async inviteNewMember (group, member) {
            try {
                const response = await this.$store.dispatch('groups/memberInvite', {
                    uuid: group.uuid,
                    firstName: member.person.first_name,
                    lastName: member.person.last_name,
                    email: member.person.email,
                    roleIds: member.roles.map(r => r.id)
                })
            
                if (member.permissions.length > 0) {
                    await this.$store.dispatch('groups/memberGrantPermission', {
                        uuid: group.uuid,
                        memberId: response.data.id,
                        permissionIds: member.permissions.map(p => p.id)
                    });
                }
            } catch (error)  {
                if (is_validation_error(error)) {
                    this.errors = error.response.data
                }
            }
        },
        async addPersonAsMember(group, member) {
            try { 
                const memberData = await this.$store.dispatch('groups/memberAdd', {
                    uuid: group.uuid,
                    personId: member.person_id,
                    roleIds: member.roles.map(r => r.id)
                })
                if (member.permissions.length > 0) {
                    await this.$store.dispatch('groups/memberGrantPermission', {
                        uuid: group.uuid,
                        memberId: memberData.id,
                        permissionIds: member.permissions.map(p => p.id)
                    });
                }
            } catch (error) {
                if (is_validation_error(error)) {
                    this.errors = error.response.data
                }
            }
        },

        async updateExistingMember(group, member) {
            this.syncRoles(group, member);
            this.syncPermissions(group, member);
        },

        syncRoles(group, member) {
            const originalMember = group.findMember(this.memberId);

            const existingRoleIds = originalMember.roles.map(r => r.id);
            const assignedRoleIds = member.roles.map(r => r.id);
            const newRoleIds = assignedRoleIds.filter(r => !existingRoleIds.includes(r));
            const removedRoleIds = existingRoleIds.filter(r => !assignedRoleIds.includes(r));
            
            if (newRoleIds.length > 0) {
                this.$store.dispatch(
                    'groups/memberAssignRole', 
                    {uuid: group.uuid, memberId: this.memberId, roleIds: newRoleIds}
                );
            }
            
            removedRoleIds.forEach(roleId => {
                this.$store.dispatch(
                    'groups/memberRemoveRole', 
                    {uuid: group.uuid, memberId: this.memberId, roleId: roleId}
                )
            })
        },

        syncPermissions(group, member) {
            const originalMember = group.findMember(this.memberId);

            const existingPermIds = originalMember.permissions.map(p => p.id);
            const assignedPermIds = member.permissions.map(p => p.id);
            const newPermIds = assignedPermIds.filter(p => !existingPermIds.includes(p));
            const removedPermIds = existingPermIds.filter(p => !assignedPermIds.includes(p));
            if (newPermIds.length > 0) {
                this.$store.dispatch(
                    'groups/memberGrantPermission', 
                    {
                        uuid: group.uuid,
                        memberId: member.id,
                        permissionIds: newPermIds
                    }
                );
            }
            removedPermIds.forEach(permId => {
                this.$store.dispatch(
                    'groups/memberRevokePermission', 
                    {
                        uuid: group.uuid,
                        memberId: member.id,
                        permissionId: permId
                    }
                )
            })
        },

        useExistingPerson(person) {
            this.newMember.person_id = person.id;
            this.newMember.person = person.clone()
        },
        isAlreadyMember(person) {
            return this.group.members.map(m => m.person.id).includes(person.id)
        },
    },
    mounted () {
        this.$store.dispatch('people/getAll', {});
        this.initNewMember();
        this.syncMember();
    }
}
</script>
<style lang="postcss" scoped>
    input:disabled {
        opacity: 1
    }
    input:disabled + label {
        @apply text-gray-700;
    }
</style>