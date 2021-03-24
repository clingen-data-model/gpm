import { createStore } from 'vuex'
import ApplicationStore from './applications.js'
import PeopleStore from './people.js'
import CdwgStore from './cdwgs.js'
import Alerts from './alerts'
import axios from '@/http/api'
import isAuthError from './../http/is_auth_error'
import module_factory from './module_factory';

const nullUser = {
    id: null,
    name: null,
    email: null,
};

const docTypeStore = module_factory({
    baseUrl: '/api/document-types', 
    namespace: 'doctypes'
})

console.log(docTypeStore);

const store = createStore({
    state: {
        hostname: process.env.VUE_APP_URL,
        user: {...nullUser},
        openRequests: [],
        authenticated: null,
        documentTypes: null
    },
    getters: {
        currentUser: (state) => state.user,
        authed: (state) => state.authenticated,
        isAuthed: (state) => state.authenticated,
    },  
    mutations: {
        setCurrentUser(state, user) {
            state.user = user
            store.commit('setAuthenticated', true)
        },
        clearCurrentUser(state) {
            state.user = {...nullUser}
            store.commit('setAuthenticated', false)
        },
        setAuthenticated(state, authVal) {
            state.authenticated = authVal
        },
    },
    actions: {
        async getCurrentUser({commit, state}) {
            if (!state.authenticated) {
                try {
                    await axios.get('/api/current-user')
                        .then(response => {
                            commit('setCurrentUser', response.data)
                        })
                } catch (error) {
                    commit('clearCurrentUser');
                }
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
        }
    },
    modules: {
        applications: ApplicationStore,
        cdwgs: CdwgStore,
        people: PeopleStore,
        alerts: Alerts,
        doctypes: docTypeStore
    }
})
export default store