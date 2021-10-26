import {ref, computed} from 'vue';
import api from '@/http/api';
import {kebabCase} from '@/utils'

export const lookups = ref({
    institutions:[],
    countries:[],
    timezones:[],
    primaryOccupations:[],
    races:[],
    ethnicities:[],
    genders:[],
});

export const profileFields = computed(() => [
    { name: 'first_name'},
    { name: 'last_name'},
    { name: 'email'},
    {
        name: 'institution_id',
        label: 'Institution',
        type: 'select',
        options: lookups.value.institutions
    },
    { name: 'credentials', type: 'textarea'},
    { name: 'biography', type: 'textarea'},
    { name: 'phone'},
    { 
        name: 'timezone', 
        label: 'Timezone',
        type: 'select', 
        options: lookups.value.timezones
    },
]);

export const demographicFields = computed(() => [
    { 
        name: 'country_id', 
        label: 'Country',
        type: 'select', 
        options: lookups.value.countries
    },
    {
        name: 'primary_occupation_id',
        label: 'Primary Occupation',
        type: 'select',
        options: lookups.value.primaryOccupations
    },
    {
        name: 'primary_occupation_other',
        label: ' ',
        type: 'text',
        placeholder: 'Details',
        show: (data) => {
            return data.primary_occupation_id == 100
        }
    },
    {
        name: 'race_id',
        label: 'Race',
        type: 'select',
        options: lookups.value.races
    },
    {
        name: 'race_other',
        label: ' ',
        type: 'text',
        placeholder: 'Details',
        show: (data) => {
            return data.race_id == 100
        }
    },
    {
        name: 'ethnicity_id',
        label: 'Ethnicity',
        type: 'select',
        options: lookups.value.ethnicities
    },
    {
        name: 'enthnicity_other',
        label: ' ',
        type: 'text',
        placeholder: 'Details',
        show: (data) => {
            return data.enthnicity_id == 100
        }
    },
    {
        name: 'gender_id',
        label: 'Gender',
        type: 'select',
        options: lookups.value.genders
    },
    {
        name: 'gender_other',
        label: ' ',
        type: 'text',
        placeholder: 'Details',
        show: (data) => {
            return data.gender_id == 100
        }
    },
]);

export const getLookups = () => {
    Object.keys(lookups.value).forEach(lkup => {
        if (lkup == 'timezones') {
            api.get(`/api/people/lookups/timezones`)
                .then(response => {
                    lookups.value.timezones = response.data.data.map(i => ({value: i, label: i}));
                })

            return;
        }
        api.get(`/api/people/lookups/${kebabCase(lkup)}`)
            .then(response => {
                lookups.value[lkup] = response.data.data.map(i => ({value: i.id, label: i.name}));
            })
            .catch(error => console.error(error));
    })
}

export default {
    profileFields,
    demographicFields,
    lookups,
    getLookups,
}