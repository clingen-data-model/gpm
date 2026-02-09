<script>
import Person from '@/domain/person'

import ProfileForm from '@/components/people/ProfileForm.vue'
import ProfilePicture from '@/components/people/ProfilePicture.vue'
import ProfilePhotoForm from '@/components/people/ProfilePhotoForm.vue'
import CredentialsView from './CredentialsView.vue'
import ExpertisesView from './ExpertisesView.vue'

export default {
    name: 'PersonProfile',
    components: {
        ProfileForm,
        ProfilePicture,
        ProfilePhotoForm,
        CredentialsView,
        ExpertisesView
    },
    props: {
        person: {
            type: Person,
            required: true,
        }
    },
    data () {
        return {
            showEditForm: false
        }
    },
    computed: {
        formDialogTitle () {
            const name = (this.userIsPerson(this.person)) ? 'your' : `${this.person.name}'s`;
            return `Edit ${name} information.`
        },
        profilePhotoComponent () {
            if (this.hasPermission('people-manage') || this.userIsPerson(this.person)) {
                return ProfilePhotoForm;
            }

            return ProfilePicture;
        }
    },
    methods: {
        editPerson () {
            this.$store.commit('people/setCurrentItemIndex', this.person);
            this.showEditForm = true;
        },
        hideEditForm () {
            this.showEditForm = false;
        }
    }
}
</script>
<template>
  <div>
    <edit-icon-button
      v-if="userIsPerson(person) || hasPermission('people-manage')"
      class="btn btn-sm float-right"
      @click="editPerson"
    >
      Edit Info
    </edit-icon-button>

    <div class="flex space-x-4">
      <div>
        <component :is="profilePhotoComponent" :person="person" style="width: 120px" />
      </div>
      <div>
        <dictionary-row
          v-for="key in ['name', 'email']" :key="key"
          class="pb-2"
          label-class="w-40"
          :label="titleCase(key)"
        >
          {{ person[key] }}
        </dictionary-row>

        <section class="mt-4 border-t pt-4">
          <h3>Profile</h3>
          <div>
            <dictionary-row class="pb-2" label-class="w-40" label="Institution">
              {{ person.institutionName }}
            </dictionary-row>
            <dictionary-row class="pb-2" label-class="w-40" label="Credentials">
              <CredentialsView :person="person" />
            </dictionary-row>
            <dictionary-row class="pb-2" label-class="w-40" label="Expertise">
              <ExpertisesView :person="person" />
            </dictionary-row>
            <dictionary-row class="pb-2" label-class="w-40" label="Biography">
              {{ person.biography }}
            </dictionary-row>
          </div>
        </section>

        <section class="mt-4 border-t pt-4">
          <div v-if="userIsPerson(person) || hasRole('super-admin') || hasRole('admin')">
            <dictionary-row class="pb-2" label-class="w-40" label="Phone">
              {{ person.phone }}
            </dictionary-row>
            <dictionary-row class="pb-2" label-class="w-40" label="Address">
              <div>
                <div v-if="person.street1">
                  {{ person.street1 }}
                </div>
                <div v-if="person.street2">
                  {{ person.street2 }}
                </div>
                <div>
                  <span v-if="person.city">{{ person.city }},</span> <span v-if="person.state">{{ person.state }}</span> <span v-if="person.zip">{{ person.zip }}</span>
                </div>
              </div>
            </dictionary-row>
          </div>
          <dictionary-row class="pb-2" label-class="w-40" label="Country">
            {{ person.country ? person.country.name : '' }}
          </dictionary-row>
          <dictionary-row class="pb-2" label-class="w-40" label="Timezone">
            {{ person.timezone }}
          </dictionary-row>
        </section>

        <section v-if="hasPermission('people-manage')" class="mt-4 border-t pt-4">
          <h3>Code of Conduct Attestation Status</h3>
          <dictionary-row class="pb-2" label-class="w-40" label="Status">
            {{ person?.coc?.status }}
          </dictionary-row>
          <dictionary-row class="pb-2" label-class="w-40" label="Version">
            {{ person?.coc?.version || 'N/A' }}
          </dictionary-row>
          <dictionary-row class="pb-2" label-class="w-40" label="Completed At">
            {{ person?.coc?.completed_at ? formatDate(person.coc.completed_at) : 'Not completed' }}
          </dictionary-row>
          <dictionary-row class="pb-2" label-class="w-40" label="Expires At">
            {{ person?.coc?.expires_at ? formatDate(person.coc.expires_at) : 'Not completed' }}
          </dictionary-row>
          <dictionary-row class="pb-2" label-class="w-40" label="Days Remaining">
            {{ person?.coc?.days_remaining !== null && person?.coc?.days_remaining !== undefined ? person.coc.days_remaining : 'N/A' }}
          </dictionary-row>
        </section>

        <section v-if="hasPermission('people-manage')" class="mt-4 border-t pt-4">
          <h3>Metadata</h3>
          <dictionary-row class="pb-2" label-class="w-40" label="Uuid">
            {{ person.uuid }}
          </dictionary-row>
          <dictionary-row class="pb-2" label-class="w-40" label="Numeric ID">
            {{ person.id }}
          </dictionary-row>

          <dictionary-row class="pb-2" label-class="w-40" label="User ID">
            {{ person.user_id || 'Account not activated.' }}
          </dictionary-row>
          <dictionary-row v-if="person.invite" class="pb-2" label-class="w-40" label="Invite Code">
            {{ person.invite.code }}
                        &nbsp;
            <span v-if="person.invite.redeemed_at"> redeemed on {{ formatDate(person.invite.redeemed_at) }}</span>
          </dictionary-row>

          <dictionary-row
            v-for="key in ['created_at', 'updated_at']" :key="key"
            class="pb-2"
            label-class="w-40"
            :label="key"
          >
            {{ formatDate(person[key]) }}
          </dictionary-row>

          <div
            v-if="hasPermission('people-manage') || userIsPerson(person)"
            class="pt-4 border-t mt-4"
          >
            <button
              class="btn btn-sm"
              @click="editPerson"
            >
              Edit Info
            </button>
          </div>
        </section>
      </div>
    </div>


    <teleport to="body">
      <modal-dialog v-model="showEditForm" :title="formDialogTitle">
        <ProfileForm :person="person" @saved="hideEditForm" @canceled="hideEditForm" />
      </modal-dialog>
    </teleport>
  </div>
</template>
