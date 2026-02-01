
import { createStore } from 'vuex'
import Alerts from '@/store/alerts'
import ApplicationStore from '@/store/applications.js'
import CdwgStore from '@/store/cdwgs.js'
import COIStore from '@/store/coi_store.js'
import GroupStore from '@/store/groups.js'
import PeopleStore from '@/store/people.js'
import CredentialStore from '@/store/credentials.js'
import ExpertiseStore from '@/store/expertises.js'
import axios from '@/http/api'
import isAuthError from '@/http/is_auth_error'
import module_factory from '@/store/module_factory';
import User from '@/domain/user'
// import router from '../router'

const docTypeStore = module_factory({
    baseUrl: '/api/document-types',
    namespace: 'doctypes'
})

const countryStore = module_factory({
    baseUrl: '/api/countries',
    namespace: 'countries'
})

let currentUserPromise = null

const store = createStore({
    state: {
        hostname: import.meta.env.VITE_APP_URL,
        user: new User(),
        openRequests: 0,
        authenticating: true,
        authenticated: null,
        documentTypes: null,
        features: {
            legacyCoi: import.meta.env.VITE_APP_LEGACY_COI === 'true',
        },
        systemInfo: {build: {}, app: { features: {}}},
    },
    getters: {
        currentUser: (state) => state.user,
        authed: (state) => state.authenticated,
        isAuthed: (state) => state.authenticated,
    },
    mutations: {
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
    },
    actions: {
        async getCurrentUser({dispatch, state}) {
            if (currentUserPromise) {
                return currentUserPromise
            }

            if (state.authenticated && state.user.id !== null) {
                return Promise.resolve();
            }

            return dispatch('forceGetCurrentUser');
        },
        async forceGetCurrentUser({commit, dispatch}) {
            currentUserPromise = (async () => {
                try {
                    const response = await axios.get('/api/current-user')
                    commit('setCurrentUser', response.data.data)
                    dispatch('getSystemInfo')
                } catch (error) {
                    // eslint-disable-next-line no-console
                    console.log(error);
                    commit('clearCurrentUser');
                }
            })()

            const localPromise = currentUserPromise

            localPromise.finally(() => {
                if (currentUserPromise === localPromise) {
                    currentUserPromise = null
                }
            })

            return currentUserPromise
        },
        async login({commit}, {email, password}) {
            await axios.get('/sanctum/csrf-cookie')
            await axios.post('/api/login', {email, password})
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
                    // eslint-disable-next-line no-alert
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
                        if (error.response.status && error.response.status === 401) {
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
        credentials: CredentialStore,
        groups: GroupStore,
        doctypes: docTypeStore,
        people: PeopleStore,
        expertises: ExpertiseStore,
        countries: countryStore,
    },
    // plugins: import.meta.env.NODE_ENV !== 'production'
    //     ? [createLogger()]
    //     : []
})

axios.interceptors.request.use((config) => {
    store.commit('addRequest');
    return config
}, (error) => {
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
                return error;
            case 403:
                if (error.response.data.includes('The request to access this resource was rejected.')) {
                    const matches = error.response.data.match(/Reference this support identifier:\s*(\d+)/)
                    const supportId = matches[1] || null;
                    store.commit('pushError', `There is a Network Firewall issue.  Please contact support GPM Support ASAP at "gpm_support@clinicalgenome.org", providing details on your network connection and the following support ID: ${supportId}`)
                } else {
                    store.commit('pushError', 'You do not have permission to complete that action.  If you think this is an error please contact support at gpm_support@clinicalgenome.org')
                }
                return error;
            case 404:
                if (error.config.headers['X-Ignore-Missing'] !== '1') {
                    store.commit('pushError', 'We couldn\'t find something you\'re looking for.');
                }
                return error;
//             case 422:
//                 store.commit('pushError', 'There was a problem with your submission');
//                 break
            case 500:
                store.commit('pushError', 'We\'ve encountered a problem trying to complete your request.  Support has been notified and we will be in touch.');
                return error;
        }
        return Promise.reject(error);
    }
);

export default store
