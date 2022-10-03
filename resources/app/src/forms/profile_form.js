import {ref, computed} from 'vue';
import api from '@/http/api';
import {kebabCase} from '@/utils'

export const lookups = ref({
    countries:[],
    primaryOccupations:[],
    races:[],
    ethnicities:[],
    genders:[],
});

export const getLookups = () => {
    Object.keys(lookups.value).forEach(lkup => {
        api.get(`/api/people/lookups/${kebabCase(lkup)}`)
            .then(response => {
                lookups.value[lkup] = response.data.data.map(i => ({value: i.id, label: i.name}));
            })
            .catch(error => console.error(error));
    })
}

export default {
    lookups,
    getLookups,
}
