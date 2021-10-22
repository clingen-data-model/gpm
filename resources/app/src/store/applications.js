import Application from '@/domain/application'
import appRepo from '@/adapters/application_repository';
import api from '@/http/api';
import { v4 as uuid4 } from 'uuid';

const baseUrl = '/api/applications';

export default {
    namespaced: true,
    state: () => ({
        requests: [],
        items: [],
        lastFetch: null,
        lastParams: {},
        currentItemIdx: null,
        lastFetchBy: null
    }),
    getters: {
        applications: state => state.items,
        all: state => state.items,
        requestCount: state => {
            return state.requests.length;
        },
        hasPendingRequests: state => {
            return state.requests.length > 0;
        },
        gceps: state => {
            return state.items.filter(app => app.expert_panel_type_id == 1);
        },
        vceps: state => {
            return state.items.filter(app => app.expert_panel_type_id == 2);
        },
        currentItem: state => {
            if (state.currentItemIdx === null) {
                return new Application();
            }

            return state.items[state.currentItemIdx]
        },
        getApplicationByUuid: (state) => (uuid) => {
            return state.items.find(app => app.uuid == uuid);
        },
    },
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
        removeItem(state, item) {
            const idx = state.items.findIndex(i => i.uuid == item.uuid);
            state.items.splice(idx, 1);           
        },
        clearCurrentItemIdx(state) {
            state.currentItemIdx = null;
        }
    },
    actions: {
        async getApplications({ commit, state, dispatch }, params, fresh = false) {
            if (fresh || state.lastFetch === null) {
                commit('setLastParams', params);
                await appRepo.all(params)
                    .then(data => {
                        data.forEach(item => {
                            commit('addApplication', item)
                        })
                        commit('setLastFetch', new Date())
                        state.lastFetchBy = 'getApplications';
                    });
                return;
            }

            dispatch('getApplicationsSinceLastFetch', params);
        },

        async getApplicationsSinceLastFetch({ commit, state }, params = null) {
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
                    })
                })
            commit('setLastFetch', new Date)
            state.lastFetchBy = 'getApplications';
        },

        async initiateApplication({ commit, dispatch }, appData) {
            await appRepo.initiate(appData)
                .then(item => {
                    commit('addApplication', item);
                    dispatch('getApplications')
                })
        },
        async getApplication({ commit }, appUuid) {
            await appRepo.find(appUuid, { with: ['logEntries', 'group.documents', 'contacts', 'logEntries.causer', 'cois', 'group.documents.type', 'nextActions'] })
                .then(item => {
                    commit('addApplication', item)
                    commit('setCurrentItemIdx', item)
                });
        },
        async updateEpAttributes({ dispatch }, application) {
            await appRepo.updateEpAttributes(application)
                .then(() => {
                    dispatch('getApplication', application.uuid);
                });
        },

        async addNextAction({ dispatch }, { application, nextActionData }) {
            if (!nextActionData.uuid) {
                nextActionData.uuid = uuid4();
            }

            return await api.post(`${baseUrl}/${application.uuid}/next-actions`, nextActionData)
                .then(() => {
                    dispatch('getApplication', application.uuid)
                })
        },

        async updateNextAction({ dispatch }, { application, updatedAction }) {
            if (!updatedAction.uuid) {
                updatedAction.uuid = uuid4();
            }

            return await api.put(`${baseUrl}/${application.uuid}/next-actions/${updatedAction.id}`, updatedAction)
                .then(() => {
                    dispatch('getApplication', application.uuid);
                });
        
        },

        async deleteNextAction({ dispatch }, {application, nextAction}) {
           const url = `/api/applications/${application.uuid}/next-actions/${nextAction.id}`
            await api.delete(url)
                .then(() => {
                    dispatch('getApplication', application.uuid);
                })
        },
        // eslint-disable-next-line
        async completeNextAction({ dispatch }, { application, nextAction, dateCompleted }) {
            const url = `/api/applications/${application.uuid}/next-actions/${nextAction.uuid}/complete`;
            await api.post(url, { date_completed: dateCompleted })
                .then(() => {
                    dispatch('getApplication', application.uuid)
                })
        },
        // eslint-disable-next-line
        async addLogEntry({ dispatch }, { application, logEntryData }) {
            const url = `/api/applications/${application.uuid}/log-entries`
            await api.post(url, logEntryData)
                .then(() => {
                    dispatch('getApplication', application.uuid);
                })
        },

        // eslint-disable-next-line
        async updateLogEntry({ dispatch }, { application, updatedEntry }) {
            const url = `/api/applications/${application.uuid}/log-entries/${updatedEntry.id}`;
            await api.put(url, updatedEntry)
                .then(() => {
                    dispatch('getApplication', application.uuid);
                })
        },

        // eslint-disable-next-line
        async deleteLogEntry({ dispatch }, { application, logEntry }) {
            const url = `/api/applications/${application.uuid}/log-entries/${logEntry.id}`;
            await api.delete(url)
                .then(() => {
                    dispatch('getApplication', application.uuid)
                });
        },

        // eslint-disable-next-line
        async addDocument({ dispatch }, { application, documentData }) {
            await appRepo.addDocument(application, documentData)
                .then((response) => {
                    dispatch('getApplication', application.uuid)
                    return response;
                })
        },

        // eslint-disable-next-line
        async markDocumentReviewed({ dispatch }, { application, document, dateReviewed, isFinal }) {
            await appRepo.markDocumentReviewed(application, document, dateReviewed, isFinal)
                .then(() => {
                    dispatch('getApplication', application.uuid)
                })
        },

        // eslint-disable-next-line
        async markDocumentVersionFinal({ dispatch }, { application, document }) {
            await api.post(`/api/applications/${application.uuid}/documents/${document.uuid}/final`)
                .then(() => {
                    dispatch('getApplication', application.uuid)
                });
        },

        //eslint-disable-next-line
        async updateDocumentInfo({ dispatch }, { application, document }) {
            return await api.put(`/api/applications/${application.uuid}/documents/${document.uuid}`, document)
                .then(() => {
                    dispatch('getApplication', application.uuid)
                });
        },

        //eslint-disable-next-line
        async deleteDocument( { dispatch }, {application, document}) {
            return await api.delete(`/api/applications/${application.uuid}/documents/${document.uuid}`)
                .then(() => {
                    dispatch('getApplication', application.uuid)
                });
        },

        // eslint-disable-next-line
        async approveCurrentStep({ dispatch }, { application, dateApproved, notifyContacts, subject, body, attachments }) {
            const formData = new FormData();
            formData.append('date_approved', dateApproved);
            formData.append('notify_contacts', notifyContacts);
            formData.append('subject', subject);
            formData.append('body', body);

            Array.from(attachments).forEach((file, idx) => {
                formData.append('attachments[' + idx + ']', file);
            });

            const url = `/api/applications/${application.uuid}/current-step/approve`
            return await api.post(
                    url,
                    formData, {
                        headers: {
                            "Content-Type": "multipart/form-data"
                        }
                    }
                )
                .then(() => {
                    dispatch('getApplication', application.uuid)
                });
        },

        // eslint-disable-next-line
        async updateApprovalDate({ dispatch }, { application, dateApproved, step }) {
            await api.put(`/api/applications/${application.uuid}/approve`, {
                    date_approved: dateApproved,
                    step: step
                })
                .then(() => {
                    dispatch('getApplication', application.uuid)
                });
        },

        // eslint-disable-next-line
        async addContact({ dispatch }, { application, contact }) {
            await appRepo.addContact(application, contact)
                .then(() => {
                    dispatch('getApplication', application.uuid);
                })
        },

        // eslint-disable-next-line
        async removeContact({ dispatch }, { application, contact }) {
            await appRepo.removeContact(application, contact)
                .then(() => {
                    dispatch('getApplication', application.uuid);
                });
        },

        async deleteApplication({ commit }, {application}) {
            return await api.delete(`/api/applications/${application.uuid}`)
                .then(() => {
                    commit('removeItem', application);
                    commit('clearCurrentItemIdx')
                })
        }


    },
}