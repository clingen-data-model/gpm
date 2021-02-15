import store from ".";
import { all as getAllApplications, initiate, addNextAction, find as findApplication} from '../adapters/application_repository';
import axios from 'axios';

export default {
    state: () => ({
        requests: [],
        items: [],
        lastFetch: null,
        lastParams: {},
    }),
    mutations: {
        addApplication(state, application) {
            const idx = state.items.findIndex(item => item.uuid == application.uuid);
            if (idx > -1) {
                state.items.splice(idx, 1, application)
                return;
            }


            state.items.push(application);
            // console.log(state.items);
        },
        clearApplications(state) {
            state.items = [];
        },
        setLastFetch(state) {
            state.lastFetch = new Date();
        },
        setLastParams(state, params) {
            state.lastParams = params;
        }
    },
    actions: {
        async getApplications({ commit, state }, params, fresh = false) {
            if (fresh || state.lastFetch === null) {
                commit('setLastParams', params);
                await getAllApplications(params)
                    .then(data => {
                        data.forEach(item => {
                            commit('addApplication', item)
                            commit('setLastFetch', new Date())
                        })
                    });
                return;
            }

            store.dispatch('getUpdatesSinceLastFetch', params);
        },
        async getUpdatesSinceLastFetch({ commit, state }, params=null) 
        {
            if (params === null) {
                params = state.lastParams
            }

            if (!params.where) {
                params.where = {}
            }
            params.where.since = state.lastFetch.toISOString()

            await getAllApplications(params)
                .then(data => {
                    data.forEach(item => {
                        commit('addApplication', item)
                        commit('setLastFetch', new Date)
                    })
                })
        },
        async initiateApplication({ commit }, appData) {
            await initiate(appData)
                .then(item => {
                    commit('addApplication', item);
                    store.dispatch('getUpdatesSinceLastFetch')
                })
        },
        async getApplication({ commit }, appUuid) {
            await findApplication(appUuid)
                .then(item => {
                    commit('addApplication', item)
                });
        },
        async addNextAction({ commit }, { application, nextActionData }) {
            await addNextAction(application, nextActionData)
                .then( response => {
                    store.dispatch('getApplication', application.uuid)
                })
        },
        async completeNextAction({ commit }, {application, nextAction, dateCompleted }) {
            const url = `/api/applications/${application.uuid}/next-actions/${nextAction.uuid}/complete`
            await axios.post(url, {date_completed: dateCompleted})
                .then (() => {
                    store.dispatch('getApplication', application.uuid)
                })
        }
    },
    getters: {
        requestCount: state => {
            return state.requests.length;
        },
        hasPendingRequests: state => {
            return state.requests.length > 0;
        },
        gceps: state => {
            return state.items.filter(app => app.ep_type_id == 1);
        },
        vceps: state => {
            return state.items.filter(app => app.ep_type_id == 2);
        },
        getApplicationByUuid: (state) => (uuid) => {
            // return uuid
            return state.items.find(app => app.uuid == uuid);
        }
        // lastUpdate: (state) {}
    }
}