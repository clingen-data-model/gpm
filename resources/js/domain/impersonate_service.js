import {api} from '@/http';

export const impersonate = (userId) => {
    return api.post(`/api/impersonate/${userId}`);
}

export const stopImpersonating = () => {
    return api.delete('/api/impersonate');
}

export const search = (searchString) => {
    return api.get(`/api/impersonate/search?query_string=${searchString}`)
        .then(response => {
            return response.data.data;
        });
}

export default {
    impersonate,
    stopImpersonating,
    search
}
