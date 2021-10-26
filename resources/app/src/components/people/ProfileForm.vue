<template>
    <div>
        <h3>Profile</h3>
        <data-form 
            :fields="profileFields" 
            v-model="profile" 
            :errors="errors"
        ></data-form>

        <hr class="my-4">

        <h3>Demographics</h3>
        <data-form 
            :fields="demographicFields" 
            v-model="profile" 
            :errors="errors"
        ></data-form>

        <button-row @submitted="save" @canceled="cancel()" submit-text="Save"></button-row>
    </div>
</template>
<script>
import isValidationError from '@/http/is_validation_error'
import {onMounted, ref} from 'vue'
import {getLookups, profileFields, demographicFields} from '@/forms/profile_form';

export default {
    name: 'ProfileForm',
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
            page: 'profile'
        }
    },
    computed: {
        fields () {
            return (this.page == 'profile') ? this.profileFields : this.demographicFields;
        },
        title () {
            return this.page == 'profile' ? 'Please fill out your profile' : 'Please share your demographic information.'
        }
    },
    methods: {
        initProfile () {
            this.profile = {...this.$store.getters['people/currentItem'].attributes};
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
                    
                this.$emit('saved');
            } catch (error) {
                if (isValidationError(error)) {
                    this.errors = error.response.data.errors;
                }
            }
        },
        cancel () {
            this.initProfile();
            this.$emit('canceled');
        }
    },
    setup () {
        onMounted(() => getLookups());

        return {
            profileFields,
            demographicFields,
            getLookups,
        }
    },
    async mounted () {
        await this.$store.dispatch('people/getPerson', {uuid: this.person.uuid})
        this.initProfile()
    }
}
</script>