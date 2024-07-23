<template>
    <div>

        <edit-icon-button
            class="btn btn-sm float-right"
            @click="editPerson"
            v-if="userIsPerson(person) || hasPermission('people-manage')"
        >
            Edit Info
        </edit-icon-button>

        <div class="flex space-x-4">
            <div>
                <component :is="profilePhotoComponent" :person="person" style="width: 120px"/>
            </div>
            <div>
                <dictionary-row class="pb-2" label-class="w-40"
                    v-for="key in ['name', 'email']"
                    :key="key"
                    :label="titleCase(key)"
                >
                    {{person[key]}}
                </dictionary-row>

                <section class="mt-4 border-t pt-4">
                    <h3>Profile</h3>
                    <div>
                        <dictionary-row class="pb-2" label-class="w-40" label="Institution">{{person.institutionName}}</dictionary-row>
                        <dictionary-row class="pb-2" label-class="w-40" label="Credentials">
                            <CredentialsView :person="person"></CredentialsView>
                        </dictionary-row>
                        <dictionary-row class="pb-2" label-class="w-40" label="Expertise">
                            <ExpertisesView :person="person" />
                        </dictionary-row>
                        <dictionary-row class="pb-2" label-class="w-40" label="Biography">{{person.biography}}</dictionary-row>
                    </div>
                </section>

                <section class="mt-4 border-t pt-4"
                    v-if="userIsPerson(person) || hasRole('super-admin') || hasRole('admin')">
                    <dictionary-row class="pb-2" label="Timezone">
                        {{person.timezone}}
                    </dictionary-row>
                    <dictionary-row class="pb-2" label-class="w-40" label="Address">
                        <div>
                            <div v-if="person.street1">{{person.street1}}</div>
                            <div v-if="person.street2">{{person.street2}}</div>
                            <div>
                                <span v-if="person.city">{{person.city}},</span> <span v-if="person.state">{{person.state}}</span> <span v-if="person.zip">{{person.zip}}</span>
                            </div>
                        </div>
                    </dictionary-row>
                    <dictionary-row class="pb-2" label-class="w-40" label="Country">
                        {{person.country ? person.country.name : ''}}
                    </dictionary-row>
                    <dictionary-row class="pb-2" label-class="w-40" label="Phone">{{person.phone}}</dictionary-row>
                </section>

                <!-- <section class="mt-4 border-t pt-4" v-if="userIsPerson(person) || hasPermission('people-manage')">
                    <header class="flex items-center space-x-2">
                        <h3>
                            Demographics
                        </h3>
                        <div>
                            <popover hover arrow>
                                <template v-slot:content>Only you and administrators can see this information.</template>
                                <icon-info class="text-gray-500" width="12" height="12"></icon-info>
                            </popover>
                        </div>
                    </header>
                    <dictionary-row class="pb-2" label-class="w-40" label="Primary Occupation">{{person.primaryOccupationName}}</dictionary-row>
                    <dictionary-row class="pb-2" label-class="w-40" label="Race">{{person.raceName}}</dictionary-row>
                    <dictionary-row class="pb-2" label-class="w-40" label="Ethnicity">{{person.ethnicityName}}</dictionary-row>
                    <dictionary-row class="pb-2" label-class="w-40" label="Gender">{{person.genderName}}</dictionary-row>
                </section> -->

                <section class="mt-4 border-t pt-4" v-if="hasPermission('people-manage')">
                    <h3>Metadata</h3>
                    <dictionary-row class="pb-2" label-class="w-40" label="Uuid">{{person.uuid}}</dictionary-row>
                    <dictionary-row class="pb-2" label-class="w-40" label="Numeric ID">{{person.id}}</dictionary-row>

                    <dictionary-row class="pb-2" label-class="w-40" label="User ID">{{person.user_id || 'Account not activated.'}}</dictionary-row>
                    <dictionary-row class="pb-2" label-class="w-40" v-if="person.invite" label="Invite Code">
                        {{person.invite.code}}
                        &nbsp;
                        <span v-if="person.invite.redeemed_at"> redeemed on {{formatDate(person.invite.redeemed_at)}}</span>
                    </dictionary-row>

                    <dictionary-row class="pb-2" label-class="w-40"
                        v-for="key in ['created_at', 'updated_at']"
                        :key="key"
                        :label="key"
                    >
                        {{formatDate(person[key])}}
                    </dictionary-row>

                    <div v-if="hasPermission('people-manage') || userIsPerson(person)"
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
                <profile-form :person="person" @saved="hideEditForm" @canceled="hideEditForm"> </profile-form>
            </modal-dialog>
        </teleport>
    </div>
</template>
<script>
import Person from '@/domain/person'

import ProfileForm from '@/components/people/ProfileForm'
import ProfilePicture from '@/components/people/ProfilePicture'
import ProfilePhotoForm from '@/components/people/ProfilePhotoForm'
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
