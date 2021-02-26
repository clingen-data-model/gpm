import { createStore } from 'vuex'
import ApplicationStore from './applications.js'
import PeopleStore from './people.js'
import CdwgStore from './cdwgs.js'
import axios from '@/http/api'
import isAuthError from './../http/is_auth_error'

export default createStore({
    state: {
        user: {
            id: null,
            name: null,
            email: null,
        },
        openRequests: [],
    },
    getters: {
        currentUser: (state) => state.user,
    },  
    mutations: {
        setCurrentUser(state, user) {
            state.user = user
        }
    },
    actions: {
        async getCurrentUser({commit}) {
            try {
                await axios.get('/api/current-user')
                    .then(response => {
                        commit(response.data)
                    })
            } catch (error) {
                if (isAuthError(error)) {
                    console.error('implement auth error handling')
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