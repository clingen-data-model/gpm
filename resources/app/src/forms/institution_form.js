import {ref, computed} from 'vue';
import {api} from '@/http';
export const countries = ref([]);

export const fields = computed(() => [
    {
        name: 'name',
        placeholder: 'University of Liliput',
    },
    {
        name: 'abbreviation',
        placeholder: 'LilU',
    },
    {
        name: 'url',
        placeholder: 'https://lilu.edu'
    },
    {
        name: 'country_id', 
        label: 'Country',
        type: 'select',
        options: countries.value
    },
])

export const getCountries = () => {
    api.get(`/api/people/lookups/countries`)
    .then(response => {
        countries.value = response.data.data.map(i => ({value: i.id, label: i.name}));
    })
    .catch(error => console.error(error));
}

export const createInstitution = async (instData) => {
    return await api.post('api/institutions', instData)
        .then(response => response.data);
}