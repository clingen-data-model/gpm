import { createStore } from 'vuex'
import ApplicationStore from './applications.js'
import PeopleStore from './people.js'
import CdwgStore from './cdwgs.js'
import axios from '@/http/api'
import isAuthError from './../http/is_auth_error'

const nullUser = {
    id: null,
    name: null,
    email: null,
};


export default createStore({
    state: {
        user: {...nullUser},
        openRequests: [],
        authenticated: false,
    },
    getters: {
        currentUser: (state) => state.user,
        authed: (state) => state.authenticated,
        isAuthed: (state) => state.authenticated,
    },  
    mutations: {
        setCurrentUser(state, user) {
            state.user = user
        },
        clearCurrentUser(state) {
            state.user = {...nullUser}
            state.authenticated = false
        },
        setAuthenticated(state, authVal) {
            state.authenticated = authVal
        }
    },
    actions: {
        async getCurrentUser({commit}) {
            try {
                await axios.get('/api/current-user')
                    .then(response => {
                        commit('setCurrentUser', response.data)
                    })
            } catch (error) {
                if (isAuthError(error)) {
                    throw('implement auth error handling')
                }
            }
        },
        async login({commit}, {email, password}) {
            await axios.get('/sanctum/csrf-cookie')
            await axios.post('/api/login', {email: email, password: password})
                .then(() => {
                    commit('setAuthenticated', true)
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
                console.log('checking auth')
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
    }
})