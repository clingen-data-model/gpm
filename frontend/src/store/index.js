import { createStore } from 'vuex'
import ApplicationStore from './applications.js'
import CdwgStore from './cdwgs.js'

export default createStore({
    state: {
        user: null,
        openRequests: [],
    },
    mutations: {},
    actions: {

    },
    modules: {
        applications: ApplicationStore,
        cdwgs: CdwgStore,
    }
})