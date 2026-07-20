import {ref, computed} from 'vue';
import {api} from '@/http';
export const countries = ref([]);

export const fields = computed(() => [
    {
        name: 'name',
        placeholder: 'University of Liliput',
        required: true,
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
        name: 'address',
        label: 'Address',
        placeholder: '123 Main Street',
    },
    {
        name: 'city',
        label: 'City',
        placeholder: 'Chapel Hill',
        required: true,
    },
    {
        name: 'country_id',
        label: 'Country',
        type: 'select',
        options: countries.value,
        required: true,
    },
    {
        name: 'reportable',
        label: 'Reportable',
        type: 'radio-group',
        options: [
            {value: true, label: 'Yes'},
            {value: false, label: 'No'}
        ]
    }
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
    return api.get(`/api/institutions`, {params})
        .then (response => response.data);
}

export const markApproved = (institution) => {
    return api.put(`/api/institutions/${institution.id}/approved`)
        .then ( response => response.data);
}

export const mergeInstitutions = (authorityId, obsoleteIds) => {
    return api.put(`/api/institutions/merge`, {authority_id: authorityId, obsolete_ids: obsoleteIds});
}

export const deleteInstitution = (institution) => {
    return api.delete(`/api/institutions/${institution.id}`);
}
