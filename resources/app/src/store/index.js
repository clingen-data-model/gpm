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


const store = createStore({
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
            store.commit('setAuthenticated', true)
        },
        clearCurrentUser(state) {
            state.user = {...nullUser}
            store.commit('setAuthenticated', false)
        },
        setAuthenticated(state, authVal) {
            state.authenticated = authVal
        }
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
                    if (isAuthError(error)) {
                        throw('implement auth error handling')
                    }
                    commit('clearCurrentUser');
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
            console.log('store.actions.checkAuth')
            if (!state.authenticated) {
                console.log('checking auth')
                await axios.get('/api/authenticated')
                    .then(() => {
                        console.log('checking auth: authed')
                        commit('setAuthenticated', true)
                    })
                    .catch(error => {
                        if (error.response.status && error.response.status == 401) {
                            console.log('checking auth: not authed')
                            commit('setAuthenticated', false)
                        }
                    })
            }
            console.log('don store.state.authenticated is true so we did not make the api call');
        }
    },
    modules: {
        applications: ApplicationStore,
        cdwgs: CdwgStore,
        people: PeopleStore,
    }
})

export default store