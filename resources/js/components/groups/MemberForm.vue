<script>
import {debounce} from 'lodash-es'
import {computed} from 'vue'
import {useStore} from 'vuex'
import {api, isValidationError} from '@/http'
import {Person} from '@/domain'
import GroupMember from '@/domain/group_member'
import MemberSuggestions from '@/components/groups/MemberSuggestions.vue'
import config from '@/configs'
import CredentialsView from '../people/CredentialsView.vue'
import ExpertisesView from '../people/ExpertisesView.vue'
import ProfileForm from '../people/ProfileForm.vue'

const groups = config.groups;

export default {
    name: 'AddMemberForm',
    components: {
        MemberSuggestions,
        CredentialsView,
        ExpertisesView,
        ProfileForm
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
    setup () {
        const store = useStore();
        const group = computed(() => store.getters['groups/currentItemOrNew'] || {});
        const people = computed(() => store.getters['people/all'] || {});
        const roles = groups.roles;
        const permissions = groups.permissions;

        return {
            group,
            people,
            roles,
            permissions,
        }
    },
    data () {
        return {
            newMember: new GroupMember(),
            errors: {},
            suggestedPeople: [],
            legendValues: [1,2],
            showProfileForm: false,
            addAnother: false
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
        needsCredentials () {
            return !this.newMember.person.credentials || this.newMember.person.credentials.length === 0;
        },
        needsExpertise () {
            return !this.newMember.person.expertises || this.newMember.person.expertises.length === 0
        },
        roleRequiresNotification () {
            return this.newMember.hasRole('coordinator') || this.newMember.hasRole('grant-liaison');
        }
    },
    watch: {
        group () {
            this.syncMember();
        },
    },
    mounted () {
        this.initNewMember();
        this.syncMember();
    },
    created () {
        this.debounceSuggestions = debounce(this.getSuggestedPeople, 500)
    },
    methods: {
        async getSuggestedPeople() {
            if (!this.newMember.first_name && !this.newMember.last_name && !this.newMember.email) {
                this.suggestedPeople = [];
                return;
            }
            const params = {
                page: 1,
                'sort[field]': 'name',
                'sort[dir]': 'ASC',
                'where[first_name]': this.newMember.first_name,
                'where[last_name]': this.newMember.last_name,
                'where[email]': this.newMember.email,
                with: ['memberships']
            }
            this.suggestedPeople = await api.get(`/api/people`, {params})
                .then(rsp => rsp.data.data.map(p => {
                    p.alreadyMember = this.isAlreadyMember(p);
                    return new Person(p);
                }));
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
                    // eslint-disable-next-line no-alert
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
        async saveAndExit () {
            if (this.roleRequiresNotification) {
                this.newMember.is_contact = true;
            }
            await this.save();
            this.clearForm();
            this.suggestedPeople = [];
            if (!this.addAnother) {
                this.$emit('saved');
            }
            if (this.newMember.id) {
                this.$router.replace({name: 'AddMember'})
            }
        },
        async saveAndEditProfile () {
            const groupMember = await this.save();
            this.newMember = new GroupMember(groupMember);
            this.showProfileForm = true;
        },
        async save () {
            try {
                if (!this.newMember.isPersisted()) {
                    if (!this.newMember.person.isPersisted()) {
                        const groupMember = await this.inviteNewMember(this.group, this.newMember);
                        this.$store.commit('pushSuccess', `${groupMember.person.name} invited to join ${groupMember.group.name}`);
                        return groupMember;

                    }
                    if (this.newMember.person.isPersisted()) {
                        const groupMember = await this.addPersonAsMember(this.group, this.newMember);
                        this.$store.commit('pushSuccess', `${groupMember.person.name} added to ${groupMember.group.name}`);
                        return groupMember;
                    }
                }
                if (this.newMember.isPersisted()) {
                    const groupMember = await this.updateExistingMember(this.group, this.newMember);
                    this.$store.commit('pushSuccess', `${groupMember.person.name}'s membership was updated.`);
                    return groupMember;
                }
            } catch (error) {
                if (isValidationError(error)) {
                    this.errors = error.response.data.errors
                    return;
                }
                throw error;
            }
        },
        async inviteNewMember (group, member) {
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

            return response.data;
        },
        async addPersonAsMember(group, member) {
            const data = {
                uuid: group.uuid,
                personId: member.person_id,
                roleIds: member.roles.map(r => r.id),
                data: {
                    is_contact: member.is_contact,
                    expertise: member.expertise,
                    notes: member.notes,
                    training_level_1: member.training_level_1,
                    training_level_2: member.training_level_2,
                }
            };
            const memberData = await this.$store.dispatch('groups/memberAdd', data);

            if (member.permissions.length > 0) {
                await this.$store.dispatch('groups/memberGrantPermission', {
                    uuid: group.uuid,
                    memberId: memberData.id,
                    permissionIds: member.permissions.map(p => p.id)
                });
            }

            return memberData;
        },

        async updateExistingMember(group, member) {
            const groupMember = await this.$store.dispatch(
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
            ).then(rsp => rsp.data);

            await this.$store.dispatch('groups/memberSyncRoles', {group, member});
            await this.syncPermissions(group, member);

            return groupMember;
        },

        async syncPermissions(group, member) {
            const originalMember = group.findMember(this.newMember.id);

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

        handleRoleChange () {
            // setTimeout(() => {
            //     if (this.newMember.hasRole('coordinator')) {
            //         this.newMember.is_contact = true;
            //     }
            // }, 1)
        },

        handleProfileUpdate (updatedPerson) {
            this.newMember.person = updatedPerson;
            this.showProfileForm = false;
            this.$store.dispatch('groups/getMembers', this.group)
        }
    }
}
</script>
<template>
  <div>
    <div class="flex">
      <div class="flex-1">
        <div v-if="!newMember.id && !newMember.person_id">
          <input-row
            label="Name"
            :errors="nameErrors"
          >
            <div class="flex space-x-2">
              <input
                v-model="newMember.person.first_name"
                type="text"
                placeholder="First"
                class="block w-1/2"
                @input="debounceSuggestions"
              >
              <input
                v-model="newMember.person.last_name"
                type="text"
                placeholder="Last"
                class="block w-1/2"
                :errors="errors.last_name"
                @input="debounceSuggestions"
              >
            </div>
          </input-row>
          <input-row
            v-model="newMember.person.email"
            label="Email"
            placeholder="example@example.com"
            input-class="w-full"
            :errors="errors.email"
            @input="debounceSuggestions"
          />
        </div>
        <div v-if="newMember.id || newMember.person_id">
          <dictionary-row label="Name">
            {{ newMember.person.name }}
          </dictionary-row>
          <dictionary-row label="Email">
            {{ newMember.person.email }}
          </dictionary-row>
          <dictionary-row label="Institution">
            {{ newMember.person.institution ? newMember.person.institution.name : '--' }}
          </dictionary-row>
          <dictionary-row label="Credentials">
            <CredentialsView :person="newMember.person" />
          </dictionary-row>
          <dictionary-row label="Expertise">
            <ExpertisesView :person="newMember.person" :legacy-expertise="newMember.legacy_expertise" />
          </dictionary-row>
          <static-alert v-if="!newMember.id">
            Adding existing person, {{ newMember.person.name }}, as a group member.
          </static-alert>
          <dictionary-row v-if="newMember.id" label="" class="text-sm">
            <popover content="As a coordinator you can edit some attributes of a group member's profile including name, email, institution, and credentials." hover arrow>
              <button class="link text-sm" @click="showProfileForm = true">
                Edit profile attributes
              </button>
            </popover>
          </dictionary-row>
          <note v-if="!newMember.id">
            You can edit the member's profile attributes from here once you've added them to your group.
          </note>
        </div>



        <input-row label="Notes" :errors="errors.notes">
          <textarea v-model="newMember.notes" rows="5" class="w-full" />
        </input-row>

        <dictionary-row label="">
          <checkbox :model-value="roleRequiresNotification || newMember.is_contact" :disabled="roleRequiresNotification" label="Receives notifications about this group" @update:model-value="$event => newMember.is_contact = $event" />
        </dictionary-row>

        <div class="border-t mt-4 pt-2">
          <h3>Group Roles</h3>
          <div class="flex flex-col h-24 flex-wrap">
            <checkbox v-for="role in roles" :key="role.id" v-model="newMember.roles" :value="role" :label="titleCase(role.name)" @input="handleRoleChange" />
          </div>
          <transition name="fade-down">
            <div
              v-if="newMember.hasRole('biocurator') && group.is_vcep"
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
        <collapsible class="border-t mt-4 pt-2 mb-2">
          <template #title>
            <h3 class="flex justify-between w-full items-center">
              Group Permissions
              <badge
                v-if="newMember.permissions.length > 0"
                color="gray"
              >
                {{ newMember.permissions.length }}
              </badge>
            </h3>
          </template>
          <div class="flex flex-col h-24 flex-wrap">
            <checkbox
              v-for="permission in permissions"
              :id="`permission-${permission.id}`"
              :key="permission.id"
              v-model="newMember.permissions"
              :value="permission"
              :disabled="newMember.hasPermissionThroughRole(permission)"
              :checked="newMember.hasPermissionThroughRole(permission)"
              :title="newMember.hasPermissionThroughRole(permission) ? `granted with role` : `grant permission`"
              :label="permission.display_name"
            />
          </div>
          <div class="px-2 py-1 bg-gray-100 border relative text-xs">
            <div class="flex space-x-2">
              <strong>Legend: </strong>
              <checkbox label="Not granted" />
              <checkbox v-model="legendValues" :value="1" label="Granted" />
              <checkbox v-model="legendValues" :value="2" disabled label="Granted w/ role" />
            </div>
            <div class="absolute top-0 left-0 w-full h-full bg-pink-500 opacity-0">
&nbsp;
            </div>
          </div>
        </collapsible>
      </div>
      <transition name="slide-fade">
        <div
          v-if="suggestedPeople.length > 0 && newMember.person_id === null"
          class="pt-2 border-l pl-2  ml-2 flex-1"
        >
          <h5 class="font-bold border-b mb-1 pb-1">
            Matching people
          </h5>
          <MemberSuggestions
            :suggestions="suggestedPeople"
            @selected="useExistingPerson"
          />
        </div>
      </transition>
    </div>
    <div>
      <!-- <div class="border-t-2 p-2 mt-4 bg-gray-100" v-if="!newMember.id">
                <label class="text-xs">
                    Add another member:
                    &nbsp;
                    <label><input type="radio" v-model="addAnother" :value="true">&nbsp;Yes</label>
                    <label><input type="radio" v-model="addAnother" :value="false">&nbsp;No</label>
                </label>
            </div> -->
      <button-row
        submit-text="Save"
        style="margin-top: 0"
        @submit="saveAndExit"
        @cancel="cancel"
      >
        <template #extra-buttons>
          <button v-if="!newMember.id" class="btn blue" @click="saveAndEditProfile">
            Save and edit profle
          </button>
        </template>
      </button-row>
    </div>
  </div>

  <teleport to="body">
    <modal-dialog v-model="showProfileForm" title="Edit Member Profile">
      <div v-if="needsCredentials || needsExpertise" class="mb-2 p-2 alert alert-warning">
        We need updated <strong v-if="needsCredentials">credentials</strong>
        <span v-if="needsExpertise && needsCredentials">and</span>
        <strong v-if="needsExpertise">expertise</strong> information for this member.
        <div v-if="needsCredentials && newMember.person.legacy_credentials">
          <strong>Legacy Credentials Data:</strong> {{ newMember.person.legacy_credentials }}
        </div>
        <div v-if="needsExpertise && newMember.legacy_expertise">
          <strong>Legacy Expertise Data:</strong> {{ newMember.legacy_expertise }}
        </div>
      </div>
      <ProfileForm
        v-if="newMember.person" :person="newMember.person"
        @saved="handleProfileUpdate"
        @canceled="showProfileForm = false"
      />
    </modal-dialog>
  </teleport>
</template>
<style lang="postcss" scoped>
    input:disabled {
        opacity: 1
    }
    input:disabled + label {
        @apply text-gray-700;
    }
</style>
