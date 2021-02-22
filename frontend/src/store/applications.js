import store from ".";
import Application from '@/domain/application'
import appRepo from '../adapters/application_repository';
import axios from 'axios';

export default {
    state: () => ({
        requests: [],
        items: [],
        lastFetch: null,
        lastParams: {},
        currentItemIdx: null
    }),
    mutations: {
        addApplication(state, application) {
            const appModel = new Application(application)
            const idx = state.items.findIndex(item => item.uuid == application.uuid);
            if (idx > -1) {
                state.items.splice(idx, 1, appModel)
                return;
            }

            state.items.push(appModel);
        },
        clearApplications(state) {
            state.items = [];
        },
        setLastFetch(state) {
            state.lastFetch = new Date();
        },
        setLastParams(state, params) {
            state.lastParams = params;
        },
        setCurrentItemIdx(state, application) {
            const idx = state.items.findIndex(i => i.uuid == application.uuid);
            state.currentItemIdx = idx;
        },
    },
    actions: {
        async getApplications({ commit, state }, params, fresh = false) {
            if (fresh || state.lastFetch === null) {
                commit('setLastParams', params);
                await appRepo.all(params)
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

            await appRepo.all(params)
                .then(data => {
                    data.forEach(item => {
                        commit('addApplication', item)
                        commit('setLastFetch', new Date)
                    })
                })
        },

        async initiateApplication({ commit }, appData) {
            await appRepo.initiate(appData)
                .then(item => {
                    commit('addApplication', item);
                    store.dispatch('getUpdatesSinceLastFetch')
                })
        },
        async getApplication({ commit }, appUuid) {
            console.log('store.applications.getApplication')
            await appRepo.find(appUuid, {with: ['logEntries', 'documents']})
                .then(item => {
                    commit('addApplication', item)
                    commit('setCurrentItemIdx', item)
                });
        },
        // eslint-disable-next-line
        async updateEpAttributes( {commit}, application) {
            console.log(application);
            await appRepo.updateEpAttributes(application)
                .then( () => {
                    store.dispatch('getApplication', application.uuid);
                });
        },
        // eslint-disable-next-line
        async addNextAction({ commit }, { application, nextActionData }) {
            await appRepo.addNextAction(application, nextActionData)
                .then( () => {
                    store.dispatch('getApplication', application.uuid)
                })
        },
        // eslint-disable-next-line
        async completeNextAction({ commit }, {application, nextAction, dateCompleted }) {
            const url = `/api/applications/${application.uuid}/next-actions/${nextAction.uuid}/complete`;
            await axios.post(url, {date_completed: dateCompleted})
                .then (() => {
                    store.dispatch('getApplication', application.uuid)
                })
        },
        // eslint-disable-next-line
        async addLogEntry ({commit}, { application, logEntryData }) {
            const url = `/api/applications/${application.uuid}/log-entries`
            await axios.post(url, logEntryData)
                .then( () => {
                    store.dispatch('getApplication', application.uuid);
                })
        },
        
        // eslint-disable-next-line
        async addDocument ({commit}, {application, documentData}) {
            await appRepo.addDocument(application, documentData)
                .then(() => {
                    store.dispatch('getApplication', application.uuid)
                })
        },

        // eslint-disable-next-line
        async markDocumentReviewed ({commit}, {application, document, dateReviewed}) {
            await appRepo.markDocumentReviewed(application, document, dateReviewed)
            .then(() => {
                store.dispatch('getApplication', application.uuid)
            })
        },

        // eslint-disable-next-line
        async approveCurrentStep ({commit}, {application, dateApproved}) {
            console.log('approveCurrentStep')
            await appRepo.approveCurrentStep(application, dateApproved)
                .then( () => {
                    console.log('approved current step')
                    store.dispatch('getApplication', application.uuid)
                });
        }


    },
    getters: {
        applications: state => state.items,
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
        currentItem: state => {
            if (state.currentItemIdx === null) {
                return new Application();
            }

            return state.items[state.currentItemIdx]
        },
        getApplicationByUuid: (state) => (uuid) => {
            // return uuid
            return state.items.find(app => app.uuid == uuid);
        },
        // lastUpdate: (state) {}
    }
}