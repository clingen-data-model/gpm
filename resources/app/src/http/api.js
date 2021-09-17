import axios from 'axios'
import store from '@/store'

const api = axios.create({
    baseURL: '',
    withCredentials: true,
    headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        common: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    },

});

api.interceptors.request.use(function (config) {
    store.commit('addRequest');
    return config
}, function (error) {
    store.commit('removeRequest');
    return Promise.reject(error)
});

api.interceptors.response.use(
    response => {
        store.commit('removeRequest');
        return response
    }, 
    error => {
        store.commit('removeRequest');
        switch (error.response.status) {
            case 401:
                store.commit('setAuthenticated', false)
                break;
            case 403:
                if (error.response.data.includes('The request to access this resource was rejected.')) {
                    const matches = error.response.data.match(/Reference this support identifier:\s*(\d+)/)
                    const supportId = matches[1] || null;
                    store.commit('pushError', 'UNC\'s security filters thinks you\'re up to no good. But I\'m sure you didn\'t mean anything by it.  Please contact support and let them know you ran intoa problem.')
                } else {
                    store.commit('pushError', 'You do not have permission to complete that action.  If you think this is an error please contact support.')
                }
                break;
            case 404:
                store.commit('pushError', 'We can\'t find what you\'re looking for.');
                break;
            case 422:
                // store.commit('pushError', 'There was a problem with your submission');
                break
            case 500:
                store.commit('pushError', 'We\'ve encountered a problem trying to complete your request.  Support has been notified and we will be in touch.');
                break;
        }
        return Promise.reject(error);
    }
);

export default api