<template>
    <div>
        <p class="text-lg font-bold">{{title}}</p>
        <data-form 
            :fields="fields" 
            v-model="profile" 
            :errors="errors"
        ></data-form>
        <div class="flex flex-row-reverse justify-between">
            <button class="btn blue" @click="goToNext">Next &gt;</button>
            <button class="link" @click="page = 'profile'" v-if="page == 'demographics'">&lt; Back to profile</button>
        </div>
        <dev-component class="mt-4">
            <collapsible>{{person}}</collapsible>
        </dev-component>
    </div>
</template>
<script>
import isValidationError from '@/http/is_validation_error'
import {onMounted} from 'vue'
import {getLookups, profileFields, demographicFields} from '@/forms/profile_form';

export default {
    name: 'ProfileForm',
    props: {
        person: {
            type: Object,
            required: true
        }
    },
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
                    })
                    
                this.$emit('saved');
            } catch (error) {
                if (isValidationError(error)) {
                    this.errors = error.response.data.errors;
                }
            }
        },
        goToNext() {
            if (this.page == 'profile') {
                if (!this.validateProfile()) {
                    return;
                }
                this.page = 'demographics';
                return;
            }
            this.save();
        },
        validateProfile() {
            this.errors = {};
            let returnValue = true;
            if (!this.profile.institution_id) {
                this.errors.institution_id = ['This field is required.']
                returnValue = false;
            }
            if (!this.profile.timezone) {
                this.errors.timezone = ['This field is required'];
                returnValue = false;
            }

            return returnValue
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