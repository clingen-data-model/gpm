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
import Person from '@/domain/person'
import isValidationError from '@/http/is_validation_error'

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
            profileFields: [
                { name: 'first_name'},
                { name: 'last_name'},
                { name: 'email'},
                {
                    name: 'institution_id',
                    label: 'Institution',
                    type: 'select',
                    options: [
                        {value: 1, label: 'beans'},
                        {value: 2, label: 'monkeys'},
                        {value: 3, label: 'beer'}
                    ]
                },
                { name: 'credentials', type: 'textarea'},
                { name: 'biography', type: 'textarea'},
                { name: 'phone'},
                { 
                    name: 'country_id', 
                    label: 'Country',
                    type: 'select', 
                    options: [
                        {value: 1, label: 'beans'},
                        {value: 2, label: 'monkeys'},
                        {value: 3, label: 'beer'}
                    ]
                },
                { 
                    name: 'timezone', 
                    label: 'Timezone',
                    type: 'select', 
                    options: [
                        {value: "Pacific/Rarotonga", label: "Pacific/Rarotonga",},
                        {value: "Pacific/Saipan", label: "Pacific/Saipan",},
                        {value: "Pacific/Tahiti", label: "Pacific/Tahiti",},
                        {value: "Pacific/Tarawa", label: "Pacific/Tarawa",},
                        {value: "Pacific/Tongatapu", label: "Pacific/Tongatapu",},
                        {value: "Pacific/Wake", label: "Pacific/Wake",},
                        {value: "Pacific/Wallis", label: "Pacific/Wallis",},
                        {value: "UTC", label: "UTC",} 
                    ]
                },

            ],
            demographicFields: [
                {
                    name: 'primary_occupation_id',
                    label: 'Primary Occupation',
                    type: 'select',
                    options: [
                        {value: 1, label: 'Clinician'},
                        {value: 2, label: 'Clinical Resercher'},
                        {value: 3, label: 'Laboratory Researcher'},
                    ]
                },
                {
                    name: 'race_id',
                    label: 'Race',
                    type: 'select',
                    options: [
                        {value: 1, label: 'American Indian or Alaska Native'},
                        {value: 2, label: 'Asian'},
                        {value: 3, label: 'Black or African Amerian'},
                    ]
                },
                {
                    name: 'ethnicity_id',
                    label: 'Ethnicity',
                    type: 'select',
                    options: [
                        {value: 1, label: 'Hispanic or Latino'},
                        {value: 2, label: 'Not Hispanic or Latino'},
                        {value: 3, label: 'Unknown'},
                    ]
                },
                {
                    name: 'gender_id',
                    label: 'Gender',
                    type: 'select',
                    options: [
                        {value: 1, label: 'Female'},
                        {value: 2, label: 'Male'},
                        {value: 3, label: 'Trans Female'},
                        {value: 4, label: 'Trans Male'},
                    ]
                },
            ],
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
                // await this.$store.dispatch(
                //         'people/updateProfile', 
                //         {uuid: this.person.uuid, attributes: this.profile}
                //     )                
                //     .then(() => {
                //         this.$store.dispatch('getCurrentUser', {force: true})
                //     })

                
                this.$emit('saved');
            } catch (error) {
                if (isValidationError(error)) {
                    this.errors = error.response.data.errors;
                }
            }
        },
        goToNext() {
            if (this.page == 'profile') {
                this.page = 'demographics';
                return;
            }
            this.save();
        }
    },
    async mounted () {
        await this.$store.dispatch('people/getPerson', {uuid: this.person.uuid})
        console.log(this.$store.getters['people/currentItem'])
        this.initProfile()
    }
}
</script>