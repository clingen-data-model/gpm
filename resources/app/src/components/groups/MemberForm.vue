<template>
    <div>
        <div class="flex">
            <div class="flex-1">
                <div v-if="!newMember.id && !newMember.person_id">
                    <input-row label="Name" 
                        :errors="nameErrors"
                    >
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
                                :errors="errors.last_name"
                            >
                        </div>
                    </input-row>
                    <input-row label="Email" 
                        v-model="newMember.person.email" 
                        placeholder="example@example.com" 
                        input-class="w-full"
                        @input="getSuggestedPeople"
                        :errors="errors.email"
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
                
                <input-row label="Expertise" :errors="errors.expertise">
                    <textarea rows="5" v-model="newMember.expertise" class="w-full"></textarea>
                </input-row>

                <input-row label="Notes" :errors="errors.notes">
                    <textarea rows="5" v-model="newMember.notes" class="w-full"></textarea>
                </input-row>

                <dictionary-row label="">
                    <checkbox v-model="newMember.is_contact" label="Receives notifications about group" />
                </dictionary-row>

                <div class="border-t mt-4 pt-2">
                    <h3>Group Roles</h3>
                    <div class="flex flex-col h-24 flex-wrap">
                        <checkbox v-for="role in roles" :key="role.id" v-model="newMember.roles" :value="role" :label="titleCase(role.name)" />
                    </div>
                    <transition name="fade-down">
                        <div 
                            v-if="newMember.hasRole('biocurator') && group.isVcep()"
                            class="border-t mt-2 pt-2 pl-2"
                        >
                            <h4>Training</h4>
                            <checkbox 
                                v-for="num in [1, 2]" :key="num" 
                                v-model="newMember[`training_level_${num}`]" 
                                :value="1"
                                :label="`Level ${num}`"
                            />
                        </div>
                    </transition>
                </div>
                <collapsible class="border-t mt-4 pt-2">
                    <template v-slot:title>
                        <h3 class="flex justify-between w-full items-center">
                            Group Permissions
                            <badge 
                                v-if="newMember.permissions.length > 0"
                                color="gray"
                            >{{newMember.permissions.length}}</badge>
                        </h3>
                    </template>
                    <div class="flex flex-col h-24 flex-wrap">
                        <checkbox
                            v-for="permission in permissions" 
                            :key="permission.id"
                            v-model="newMember.permissions" 
                            :value="permission"
                            :disabled="newMember.hasPermissionThroughRole(permission)"
                            :checked="newMember.hasPermissionThroughRole(permission)"
                            :id="`permission-${permission.id}`"
                            :title="newMember.hasPermissionThroughRole(permission) ? `granted with role` : `grant permission`"
                            :label="permission.name"
                        />
                    </div>
                    <div class="px-2 py-1 bg-gray-100 border relative text-xs">
                        <div class="flex space-x-2">
                        <strong>Legend: </strong>
                            <checkbox label="Not granted" />
                            <checkbox :checked="true" label="Granted" />
                            <checkbox :checked="true" disabled label="Granted w/ role" />
                        </div>
                        <div class="absolute top-0 left-0 w-full h-full bg-pink-500 opacity-0">&nbsp;</div>
                    </div>
                </collapsible>

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
        <!-- <dev-todo class="mt-8" :items="[]"></dev-todo> -->
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
    computed: {
        originalMember () {
            return this.group.findMember(this.memberId);
        },
        nameErrors () {
            return [this.errors.first_name, this.errors.last_name]
                    .flat()
                    .filter(i => i);
        },
    },
    setup () {
        const store = useStore();
        let group = computed(() => store.getters['groups/currentItemOrNew'] || {});
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
                    this.newMember = this.originalMember.clone();
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
            try {
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
            } catch (error) {
                if (is_validation_error(error)) {
                    this.errors = error.response.data.errors
                }
            }
        },
        async inviteNewMember (group, member) {
            try {
                const response = await this.$store.dispatch('groups/memberInvite', {
                    uuid: group.uuid,
                    data: {
                        firstName: member.person.first_name,
                        lastName: member.person.last_name,
                        email: member.person.email,
                        roleIds: member.roles.map(r => r.id),
                        isContact: member.is_contact,
                        expertise: member.expertise,
                        notes: member.notes,
                        training_level_1: member.training_level_1,
                        training_level_2: member.training_level_2,
                    }
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
                    this.errors = error.response.data.errors
                }
            }
        },
        async addPersonAsMember(group, member) {
            try { 
                const memberData = await this.$store.dispatch('groups/memberAdd', {
                    uuid: group.uuid,
                    personId: member.person_id,
                    roleIds: member.roles.map(r => r.id),
                    isContact: member.is_contact,
                    expertise: member.expertise,
                    notes: member.notes,
                    training_level_1: member.training_level_1,
                    training_level_2: member.training_level_2,
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
                    this.errors = error.response.data.errors
                }
            }
        },

        async updateExistingMember(group, member) {
            await this.$store.dispatch(
                'groups/memberUpdate', 
                {
                    groupUuid: group.uuid, 
                    memberId: member.id, 
                    data: {
                        is_contact: this.newMember.is_contact,
                        expertise: this.newMember.expertise,
                        notes: this.newMember.notes,
                        training_level_1: this.newMember.training_level_1,
                        training_level_2: this.newMember.training_level_2,
                    }
                }
            )

            await this.$store.dispatch('groups/memberSyncRoles', {group, member});
            await this.syncPermissions(group, member);
        },

        async syncPermissions(group, member) {
            const originalMember = group.findMember(this.memberId);

            const existingPermIds = originalMember.permissions.map(p => p.id);
            const assignedPermIds = member.permissions.map(p => p.id);
            const newPermIds = assignedPermIds.filter(p => !existingPermIds.includes(p));
            const removedPermIds = existingPermIds.filter(p => !assignedPermIds.includes(p));
            // const promises = [];
            
            if (newPermIds.length > 0) {
                // promises.push(
                    this.$store.dispatch(
                        'groups/memberGrantPermission', 
                        {
                            uuid: group.uuid,
                            memberId: member.id,
                            permissionIds: newPermIds
                        }
                    )
                // );
            }
            
            removedPermIds.forEach(permId => {
                // promises.push(
                    this.$store.dispatch(
                        'groups/memberRevokePermission', 
                        {
                            uuid: group.uuid,
                            memberId: member.id,
                            permissionId: permId
                        }
                    )
                // );
            });

            // await Promise.all(promises);
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