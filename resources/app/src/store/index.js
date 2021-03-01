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
    },
    getters: {
        currentUser: (state) => state.user,
        authed: (state) => Boolean(state.user.id)
    },  
    mutations: {
        setCurrentUser(state, user) {
            state.user = user
        },
        clearCurrentUser(state) {
            state.user = {...nullUser}
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
        // eslint-disable-next-line
        async login({commit}, {email, password}) {
            await axios.get('/sanctum/csrf-cookie')
            await axios.post('/api/login', {email: email, password: password});
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
        }
    },
    modules: {
        applications: ApplicationStore,
        cdwgs: CdwgStore,
        people: PeopleStore,
    }
})