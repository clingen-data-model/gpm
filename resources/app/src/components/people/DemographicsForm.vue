<template>
    <div>
        <p class="font-bold text-lg">Please share your demographic information.</p>
        <data-form 
            :fields="fields" 
            :errors="errors" 
            v-model="demographics"
        ></data-form>
        <div class="flex justify-between">
            <button class="link" @click="back">&lt; Back to Profile</button>
            <button class="btn blue" @click="save">Next ></button>
        </div>
    </div>
</template>
<script>
import Person from '@/domain/person'

export default {
    name: 'DemographicsForm',
    props: {
        person: {
            type: Person,
            required: true
        }
    },
    data() {
        return {
            fields: [
                {
                    name: 'primary_occupation_id',
                    label: 'Primary Occupation',
                    type: 'select',
                    options: [
                        {value: 1, label: 'dog euthenizer'},
                        {value: 2, label: 'dog strangler'},
                        {value: 3, label: 'dog ignorer'},
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
            errors: {},
            demographics: {}
        }
    },
    computed: {
    },
    methods: {
        initDemographics () {
            this.demographics = {...this.person.attributes}
        },
        back() {
            this.$emit('back')
        },
        save() {
            this.$emit('saved')
        }
    },
    mounted() {
        this.initDemographics();
    }
}
</script>