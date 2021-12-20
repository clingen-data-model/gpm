<template>
    <div>

        <edit-icon-button 
            class="btn btn-sm float-right" 
            @click="editPerson"
            v-if="userIsPerson(person) || hasPermission('people-manage')"
        >
            Edit Info
        </edit-icon-button>


        <dictionary-row class="pb-2" label-class="w-40" 
            v-for="key in ['name', 'email']"
            :key="key"
            :label="titleCase(key)"
        >
            {{person[key]}}
        </dictionary-row>

        <section class="mt-4 border-t pt-4">
            <h3>Profile</h3>
            <dictionary-row class="pb-2" label-class="w-40" label="Institution">{{person.institutionName}}</dictionary-row>
            <dictionary-row class="pb-2" label-class="w-40" label="Credentials">{{person.credentials}}</dictionary-row>
            <dictionary-row class="pb-2" label-class="w-40" label="Primary Occupation">{{person.primaryOccupationName}}</dictionary-row>
            <dictionary-row class="pb-2" label-class="w-40" label="Biography">{{person.biography}}</dictionary-row>
            <!-- <dictionary-row class="pb-2" label-class="w-40" label="ORCID">{{person.orcid_id}}</dictionary-row>
            <dictionary-row class="pb-2" label-class="w-40" label="Hypothes.is ID">{{person.hypothesis_id}}</dictionary-row> -->
        </section>

        <section class="mt-4 border-t pt-4">
            <dictionary-row class="pb-2" label-class="w-40" label="Address">
                <p v-if="person.street1">{{person.street1}}</p>
                <p v-if="person.street2">{{person.street2}}</p>
                <p>
                    <span v-if="person.city">{{person.city}},</span>
                    <span v-if="person.state">{{person.state}}</span>
                    <span v-if="person.zip">{{person.zip}}</span>
                </p>
                <p v-if="person.country">{{person.country.name}}</p>
            </dictionary-row>
            <dictionary-row class="pb-2" label-class="w-40" label="Phone">{{person.phone}}</dictionary-row>
        </section>

        <section class="mt-4 border-t pt-4" v-if="userIsPerson(person) || hasPermission('people-manage')">
            <header class="flex items-center space-x-2">
                <h3>
                    Demographics
                </h3>
                <popper hover arrow>
                    <template v-slot:content>Only you and administrators can see this information</template>
                    <icon-info class="text-gray-500"></icon-info>
                </popper>
            </header>    
            <dictionary-row class="pb-2" label-class="w-40" label="Primary Occupation">{{person.primaryOccupationName}}</dictionary-row>
            <dictionary-row class="pb-2" label-class="w-40" label="Race">{{person.raceName}}</dictionary-row>
            <dictionary-row class="pb-2" label-class="w-40" label="Ethnicity">{{person.ethnicityName}}</dictionary-row>
            <dictionary-row class="pb-2" label-class="w-40" label="Gender">{{person.genderName}}</dictionary-row>
        </section>

        <section class="mt-4 border-t pt-4" v-if="hasPermission('people-manage')">
            <h3>Metadata</h3>
            <dictionary-row class="pb-2" label-class="w-40" label="Uuid">{{person.uuid}}</dictionary-row>
            <dictionary-row class="pb-2" label-class="w-40" label="Numeric ID">{{person.id}}</dictionary-row>

            <dictionary-row class="pb-2" label-class="w-40" v-if="person.user_id" label="User ID">{{person.user_id}}</dictionary-row>
            <dictionary-row class="pb-2" label-class="w-40" v-else label="Invite Code">
                {{person.invite ? person.invite.code : null}}
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

        <teleport to="body">
            <modal-dialog v-model="showEditForm" :title="formDialogTitle">
                <profile-form :person="person" @saved="hideEditForm" @canceled="hideEditForm"> </profile-form>
            </modal-dialog>
        </teleport>
    </div>
</template>
<script>
import Person from '@/domain/person'

// import PersonForm from '@/components/people/PersonForm'
import ProfileForm from '@/components/people/ProfileForm'

export default {
    name: 'PersonProfile',
    components: {
        ProfileForm,
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