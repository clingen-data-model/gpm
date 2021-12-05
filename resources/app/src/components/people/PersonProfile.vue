<template>
    <div>

        <edit-icon-button 
            class="btn btn-sm float-right" 
            @click="editPerson"
        >
            Edit Info
        </edit-icon-button>


        <dictionary-row 
            v-for="key in ['name', 'email']"
            :key="key"
            :label="titleCase(key)"
        >
            {{person[key]}}
        </dictionary-row>

        <section class="mt-4 border-t pt-4">
            <h3>Profile</h3>
            <dictionary-row label="Institution">{{person.institutionName}}</dictionary-row>
            <dictionary-row label="Credentials">{{person.credentials}}</dictionary-row>
            <dictionary-row label="Primary Occupation">{{person.primaryOccupationName}}</dictionary-row>
            <dictionary-row label="Biography">{{person.biography}}</dictionary-row>
            <dictionary-row label="ORCID">{{person.orcid_id}}</dictionary-row>
            <dictionary-row label="Hypothes.is ID">{{person.hypothesis_id}}</dictionary-row>
        </section>

        <section class="mt-4 border-t pt-4">
            <dictionary-row label="Address">
                <p v-if="person.street1">{{person.street1}}</p>
                <p v-if="person.street2">{{person.street2}}</p>
                <p>
                    <span v-if="person.city">{{person.city}},</span>
                    <span v-if="person.state">{{person.state}}</span>
                    <span v-if="person.zip">{{person.zip}}</span>
                </p>
                <p v-if="person.country">{{person.country.name}}</p>
            </dictionary-row>
            <dictionary-row label="Phone">{{person.phone}}</dictionary-row>
        </section>

        <section class="mt-4 border-t pt-4" v-if="userIsPerson(person) || hasPermission('people-manage')">
            <h3>Demographics</h3>
            <dictionary-row label="Primary Occupation">{{person.primaryOccupationName}}</dictionary-row>
            <dictionary-row label="Race">{{person.raceName}}</dictionary-row>
            <dictionary-row label="Ethnicity">{{person.ethnicityName}}</dictionary-row>
            <dictionary-row label="Gender">{{person.genderName}}</dictionary-row>
        </section>

        <section class="mt-4 border-t pt-4" v-if="hasPermission('people-manage')">
            <h3>Metadata</h3>
            <dictionary-row label="Uuid">{{person.uuid}}</dictionary-row>
            <dictionary-row label="Numeric ID">{{person.id}}</dictionary-row>

            <dictionary-row v-if="person.user_id" label="User ID">{{person.user_id}}</dictionary-row>
            <dictionary-row v-else label="Invite Code">
                {{person.invite ? person.invite.code : null}}
            </dictionary-row>
            
            <dictionary-row 
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
            // default: () => new Person()
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