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

export const createInstitution = (instData) => {
    return api.post('/api/institutions', instData)
        .then(response => response.data);
}

export const updateInstitution = (instData) => {
    return api.put(`/api/institutions/${instData.id}`, instData)
            .then(response => response.data);
}

export const getInstitution = (id) => {
    return api.get(`/api/institutions/${id}`)
        .then (response => response.data);
}

export const getAllInstitutions = (params) => {
    return api.get(`/api/institutions`, {params: params})
        .then (response => response.data);
}

export const markApproved = (institution) => {
    return api.put(`/api/institutions/${institution.id}/approved`)
        .then ( response => response.data);
}