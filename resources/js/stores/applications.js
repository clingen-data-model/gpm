import { defineStore } from 'pinia'
import Application from '@/domain/application'
import appRepo from '@/adapters/application_repository'
import { api, queryStringFromParams } from '@/http'
import { v4 as uuid4 } from 'uuid'

const baseUrl = '/api/applications'

export const useApplicationsStore = defineStore('applications', {
    state: () => ({
        requests: [],
        items: [],
        lastFetch: null,
        lastParams: {},
        currentItemIdx: null,
        lastFetchBy: null,
    }),
    getters: {
        applications: state => state.items,
        requestCount: state => state.requests.length,
        hasPendingRequests: state => state.requests.length > 0,
        gceps: state => state.items.filter(app => app.expert_panel_type_id === 1),
        vceps: state => state.items.filter(app => app.expert_panel_type_id === 2),
        currentItem: state => {
            if (state.currentItemIdx === null) {
                return new Application()
            }
            return state.items[state.currentItemIdx]
        },
        getApplicationByUuid: state => uuid => state.items.find(app => app.uuid === uuid),
    },
    actions: {
        addApplication(application) {
            const appModel = new Application(application)
            const idx = this.items.findIndex(item => item.uuid === application.uuid)
            if (idx > -1) {
                this.items.splice(idx, 1, appModel)
                return
            }
            this.items.push(appModel)
        },
        clearApplications() {
            this.items = []
        },
        setLastFetch() {
            this.lastFetch = new Date()
        },
        setLastParams(params) {
            this.lastParams = params
        },
        setCurrentItemIndex(application) {
            const idx = this.items.findIndex(i => i.uuid === application.uuid)
            this.currentItemIdx = idx
        },
        removeItem(item) {
            const idx = this.items.findIndex(i => i.uuid === item.uuid)
            this.items.splice(idx, 1)
        },
        clearCurrentItemIdx() {
            this.currentItemIdx = null
        },
        getApplications(params) {
            api.get(baseUrl + queryStringFromParams(params))
                .then(response => {
                    response.data.data.forEach(item => this.addApplication(item))
                })
        },
        async initiateApplication(appData) {
            await appRepo.initiate(appData)
                .then(item => {
                    this.addApplication(item)
                    this.getApplications()
                })
        },
        async getApplication(appUuid) {
            await appRepo.find(appUuid, {
                with: [
                    'group.logEntries',
                    'group.documents',
                    'contacts',
                    'group.logEntries.causer',
                    'cois',
                    'group.documents.type',
                    'nextActions',
                ],
            })
            .then(item => {
                this.addApplication(item)
                this.setCurrentItemIndex(item)
            })
        },
        async updateEpAttributes(application) {
            await appRepo.updateEpAttributes(application)
                .then(() => {
                    this.getApplication(application.uuid)
                })
        },
        async addNextAction({ application, nextActionData }) {
            if (!nextActionData.uuid) {
                nextActionData.uuid = uuid4()
            }
            return await api.post(`${baseUrl}/${application.uuid}/next-actions`, nextActionData)
                .then(response => {
                    application.nextActions.push(response.data)
                    return response
                })
        },
        async updateNextAction({ application, updatedAction }) {
            if (!updatedAction.uuid) {
                updatedAction.uuid = uuid4()
            }
            return await api.put(`${baseUrl}/${application.uuid}/next-actions/${updatedAction.id}`, updatedAction)
                .then(response => {
                    const naIdx = application.nextActions.findIndex(na => na.id === response.data.id)
                    application.nextActions[naIdx] = response.data
                    return response
                })
        },
        async deleteNextAction({ application, nextAction }) {
            const url = `/api/applications/${application.uuid}/next-actions/${nextAction.id}`
            await api.delete(url)
                .then(response => {
                    const naIdx = application.nextActions.findIndex(na => na.id === nextAction.id)
                    application.nextActions.splice(naIdx, 1)
                    return response
                })
        },
        async completeNextAction({ application, nextAction, dateCompleted }) {
            const url = `/api/applications/${application.uuid}/next-actions/${nextAction.uuid}/complete`
            await api.post(url, { date_completed: dateCompleted })
                .then(response => {
                    const naIdx = application.nextActions.findIndex(na => na.id === response.data.id)
                    application.nextActions[naIdx] = response.data
                    return response
                })
        },
        async addLogEntry({ application, logEntryData }) {
            const url = `/api/applications/${application.uuid}/log-entries`
            await api.post(url, logEntryData)
                .then(() => {
                    this.getApplication(application.uuid)
                })
        },
        async updateLogEntry({ application, updatedEntry }) {
            const url = `/api/applications/${application.uuid}/log-entries/${updatedEntry.id}`
            await api.put(url, updatedEntry)
                .then(() => {
                    this.getApplication(application.uuid)
                })
        },
        async deleteLogEntry({ application, logEntry }) {
            const url = `/api/applications/${application.uuid}/log-entries/${logEntry.id}`
            await api.delete(url)
                .then(() => {
                    this.getApplication(application.uuid)
                })
        },
        async addDocument({ application, documentData }) {
            await appRepo.addDocument(application, documentData)
                .then(response => {
                    this.getApplication(application.uuid)
                    return response
                })
        },
        async markDocumentReviewed({ application, document, dateReviewed, isFinal }) {
            await appRepo.markDocumentReviewed(application, document, dateReviewed, isFinal)
                .then(() => {
                    this.getApplication(application.uuid)
                })
        },
        async markDocumentVersionFinal({ application, document }) {
            await api.post(`/api/applications/${application.uuid}/documents/${document.uuid}/final`)
                .then(response => {
                    const oldFinalIdx = application.documents.findIndex(d => d.metadata.is_final === 1)
                    const oldFinal = application.documents[oldFinalIdx]
                    if (oldFinal) {
                        oldFinal.metadata.is_final = 0
                        oldFinal.is_final = 0
                        application.documents[oldFinalIdx] = oldFinal
                    }
                    const docIdx = application.documents.findIndex(d => d.id === response.data.id)
                    application.documents[docIdx] = response.data
                })
        },
        async updateDocumentInfo({ application, document }) {
            return await api.putForm(`/api/applications/${application.uuid}/documents/${document.uuid}`, document)
                .then(() => {
                    this.getApplication(application.uuid)
                })
        },
        async deleteDocument({ application, document }) {
            return await api.delete(`/api/applications/${application.uuid}/documents/${document.uuid}`)
                .then(() => {
                    this.getApplication(application.uuid)
                })
        },
        async approveCurrentStep({ application, dateApproved, notifyContacts, subject, body, attachments }) {
            const formData = new FormData()
            formData.append('date_approved', dateApproved)
            formData.append('notify_contacts', notifyContacts)
            formData.append('subject', subject)
            formData.append('body', body)
            Array.from(attachments).forEach((file, idx) => {
                formData.append(`attachments[${idx}]`, file)
            })
            const url = `/api/applications/${application.uuid}/current-step/approve`
            return await api.postForm(url, formData)
                .then(() => {
                    this.getApplication(application.uuid)
                })
        },
        async updateApprovalDate({ application, dateApproved, step }) {
            await api.put(`/api/applications/${application.uuid}/approve`, {
                date_approved: dateApproved,
                step,
            })
            .then(() => {
                this.getApplication(application.uuid)
            })
        },
        async addContact({ application, contact }) {
            await appRepo.addContact(application, contact)
                .then(() => {
                    this.getApplication(application.uuid)
                })
        },
        async removeContact({ application, contact }) {
            await appRepo.removeContact(application, contact)
                .then(() => {
                    this.getApplication(application.uuid)
                })
        },
        async deleteApplication({ application }) {
            return await api.delete(`/api/applications/${application.uuid}`)
                .then(() => {
                    this.removeItem(application)
                    this.clearCurrentItemIdx()
                })
        },
    },
})
