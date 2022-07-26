<template>
    <div>
        <h3>Profile</h3>
        <div class="float-right" v-if="(hasPermission('people-manage') || userIsPerson(person))"
        >
            <profile-photo-form :person="person" style="width: 100px; height: 100px;" />
        </div>
        <data-form 
            :fields="fields" 
            v-model="profile" 
            :errors="errors"
        ></data-form>
        <hr class="my-4">

        <button-row @submitted="save" @canceled="cancel()" submit-text="Save"></button-row>
    </div>
</template>
<script>
import isValidationError from '@/http/is_validation_error'
import {onMounted} from 'vue'
import {getLookups, profileFields, demographicFields, lookups} from '@/forms/profile_form';
import ProfilePhotoForm from '@/components/people/ProfilePhotoForm.vue';
export default {
    name: 'ProfileForm',
    components: {
        ProfilePhotoForm
    },
    props: {
        person: {
            type: Object,
            required: true
        }
    },
    emits: [
        'saved',
        'canceled'
    ],
    data() {
        return {
            errors: {},
            profile: {},
            page: 'profile',
            institutionId: null,
            timezone: null
        }
    },
    computed: {
        fields () {
            let editableFields = [...this.profileFields];
            if (!this.hasPermission('people-manage') && !this.userIsPerson(this.person)) {
                editableFields =  editableFields.filter(f => ['first_name', 'last_name', 'email', 'credentials'].includes(f.name));
            }
            return editableFields;

            // return (this.page == 'profile') ? this.profileFields : this.demographicFields;
        },
        title () {
            return this.page == 'profile' ? 'Please fill out your profile' : 'Please share your demographic information.'
        }
    },
    watch: {
        person () {
            this.initProfile();
        }
    },
    methods: {
        initProfile () {
            this.profile = {...this.person.attributes};
        },
        async save () {
            try {
                await this.$store.dispatch(
                        'people/updateProfile', 
                        {uuid: this.person.uuid, attributes: this.profile}
                    )                
                    .then(() => {
                        this.$store.dispatch('getCurrentUser', {force: true})
                        this.$store.commit('pushSuccess', 'Your profile has been updated.')
                    })
                    
                this.errors = {};
                this.$emit('saved');
            } catch (error) {
                if (isValidationError(error)) {
                    this.errors = error.response.data.errors;
                }
            }
        },
        cancel () {
            this.initProfile();
            this.errors = {};
            this.$emit('canceled');
        }
    },
    setup () {
        onMounted(() => getLookups());

        return {
            profileFields,
            demographicFields,
            getLookups,
            lookups,
        }
    },
    async mounted () {
        this.initProfile()
    }
}
</script>