// eslint-disable-next-line
import { createStore } from 'vuex'
import Alerts from '@/store/alerts'
import ApplicationStore from '@/store/applications.js'
import CdwgStore from '@/store/cdwgs.js'
import COIStore from '@/store/coi_store.js'
import GroupStore from '@/store/groups.js'
import PeopleStore from '@/store/people.js'
import axios from '@/http/api'
import isAuthError from '@/http/is_auth_error'
import module_factory from '@/store/module_factory';
import User from '@/domain/user'
// import router from '../router'


axios.interceptors.request.use(function (config) {
    store.commit('addRequest');
    return config
}, function (error) {
    store.commit('removeRequest');
    return Promise.reject(error)
});

axios.interceptors.response.use(
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
                    store.commit('pushError', 'UNC\'s security filters thinks you\'re up to no good. But I\'m sure you didn\'t mean anything by it.  Please contact support at https://help.unc.edu and click the "Report an Issue" button and let them know you ran into a problem.  Be sure to include the followng support ID: '+supportId)
                } else {
                    store.commit('pushError', 'You do not have permission to complete that action.  If you think this is an error please contact support.')
                }
                break;
            case 404:
                store.commit('pushError', 'We can\'t find what you\'re looking for.');
                break;
//             case 422:
//                 store.commit('pushError', 'There was a problem with your submission');
//                 break
            case 500:
                store.commit('pushError', 'We\'ve encountered a problem trying to complete your request.  Support has been notified and we will be in touch.');
                break;
        }
        return Promise.reject(error);
    }
);

const docTypeStore = module_factory({
    baseUrl: '/api/document-types', 
    namespace: 'doctypes'
})

export const mutations = {
    addRequest(state) {
        state.openRequests++;
    },
    removeRequest(state) {
        state.openRequests--;
    },
    setCurrentUser(state, user) {
        state.user = new User(user)
        store.commit('setAuthenticated', true)
    },
    clearCurrentUser(state) {
        state.user = new User();
        store.commit('setAuthenticated', false)
    },
    setAuthenticated(state, authVal) {
        state.authenticated = authVal
    },
    startAuthenticating(state) {
        state.authenticating = true;
    },
    stopAuthenticating(state) {
        state.authenticating = false;
    },
};

const store = createStore({
    state: {
        hostname: process.env.VUE_APP_URL,
        user: new User(),
        openRequests: 0,
        authenticating: true,
        authenticated: null,
        documentTypes: null,
        features: {
            legacyCoi: process.env.VUE_APP_LEGACY_COI == 'true',
        },
        systemInfo: {build: {}},
    },
    getters: {
        currentUser: (state) => state.user,
        authed: (state) => state.authenticated,
        isAuthed: (state) => state.authenticated,
    },  
    mutations: mutations,
    actions: {
        async getCurrentUser({commit, dispatch, state}) {
            if (!state.authenticated || state.user.id === null) {
                try {
                    await axios.get('/api/current-user')
                        .then(response => {
                            commit('setCurrentUser', response.data.data)
                        })
                        dispatch('getSystemInfo');
                    } catch (error) {
                    commit('clearCurrentUser');
                }
            }
        },
        async forceGetCurrentUser({commit}) {
            try {
                await axios.get('/api/current-user')
                    .then(response => {
                        commit('setCurrentUser', response.data.data)
                    })
            } catch (error) {
                commit('clearCurrentUser');
            }
        },
        async login({commit}, {email, password}) {
            await axios.get('/sanctum/csrf-cookie')
            await axios.post('/api/login', {email: email, password: password})
                .then(() => {
                    commit('setAuthenticated', true);
                    store.dispatch('getCurrentUser', true);
                });
        },
        async logout({commit}) {
            try {
                await axios.post('/api/logout')
                    .then(() => {
                        commit('clearCurrentUser')
                    });
            } catch (error) {
                if (isAuthError(error)) {
                    alert('You cannot log out because you are not logged in');
                }
            }
        },
        async checkAuth({commit, state}) {
            if (!state.authenticated) {
                await axios.get('/api/authenticated')
                    .then(() => {
                        commit('setAuthenticated', true)
                    })
                    .catch(error => {
                        if (error.response.status && error.response.status == 401) {
                            commit('setAuthenticated', false)
                        }
                    })
            }
        },
        getSystemInfo ({state}) {
            if (state.authenticated) {
                axios.get('/api/system-info')
                    .then(response => {
                        state.systemInfo = response.data
                    })
                
            }
        }
    },
    modules: {
        alerts: Alerts,
        applications: ApplicationStore,
        coiStore: COIStore,
        cdwgs: CdwgStore,
        groups: GroupStore,
        doctypes: docTypeStore,
        people: PeopleStore,
    },
    // plugins: process.env.NODE_ENV !== 'production'
    //     ? [createLogger()]
    //     : []
})
export default store