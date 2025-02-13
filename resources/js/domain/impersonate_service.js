import {api} from '@/http';

export const impersonate = (userId) => {
    sessionStorage.clear();
    window.location.href = '/impersonate/take/'+userId;
}

export const search = (searchString) => {
    return api.get(`/api/impersonate/search?query_string=${searchString}`)
        .then(response => {
            return response.data.data;
        });
}

export default {
    impersonate,
    search
}