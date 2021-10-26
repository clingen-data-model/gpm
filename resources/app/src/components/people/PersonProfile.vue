<template>
    <div>
        <dictionary-row 
            v-for="key in ['name', 'email']"
            :key="key"
            :label="key"
        >
            {{person[key]}}
        </dictionary-row>
        <hr>
        <dict-row label="Institution">{{person.institutioName}}</dict-row>
        <dict-row label="Credentials">{{person.credentials}}</dict-row>
        <dict-row label="Primary Occupation">{{person.primaryOccupationName}}</dict-row>
        <dict-row label="Biography">{{person.biography}}</dict-row>
        <dict-row label="ORCID">{{person.orcid_id}}</dict-row>
        <dict-row label="Hypothes.is ID">{{person.hypothesis_id}}</dict-row>
        <hr>
        <dict-row label="Address">
            <p v-if="person.street1">{{person.street1}}</p>
            <p v-if="person.street2">{{person.street2}}</p>
            <p>
                <span v-if="person.city">{{person.city}},</span>
                <span v-if="person.state">{{person.state}}</span>
                <span v-if="person.zip">{{person.zip}}</span>
            </p>
            <p v-if="person.country">{{person.country.name}}</p>
        </dict-row>
        <dict-row label="Phone">{{person.phone}}</dict-row>
        <hr>
        <dictionary-row 
            v-for="key in ['created_at', 'updated_at']"
            :key="key"
            :label="key"
        >
            {{formatDate(person[key])}}
        </dictionary-row>
        <div v-if="hasPermission('people-manage') || useIsPerson(person)"
            class="pt-4 border-t mt-4"
        >
            <button 
                class="btn btn-sm" 
                @click="editPerson"
            >
                Edit Info
            </button>
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
            default: () => new Person()
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