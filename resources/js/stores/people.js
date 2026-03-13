import { defineStore } from 'pinia'
import { api, queryStringFromParams } from '@/http'
import Person from '@/domain/person'

const baseUrl = '/api/people'

export const usePeopleStore = defineStore('people', {
    state: () => ({
        requests: [],
        items: [],
        lastFetch: null,
        lastParams: null,
        currentItemIdx: null,
    }),
    getters: {
        people: state => state.items,
        personWithUuid: state => uuid => state.items.find(i => i.uuid === uuid),
        getPersonWithUuid: state => uuid => state.items.find(i => i.uuid === uuid),
        indexForPersonWithUuid: state => uuid => state.items.findIndex(i => i.uuid === uuid),
        currentItem: state => {
            if (typeof state.currentItemIdx === 'undefined' || state.currentItemIdx === null || state.currentItemIdx < 0) {
                return new Person()
            }
            return state.items[state.currentItemIdx]
        },
    },
    actions: {
        addItem(itemData) {
            let person = itemData
            if (!(itemData instanceof Person)) {
                person = new Person(itemData)
            }
            const idx = this.items.findIndex(item => item.uuid === itemData.uuid)
            if (idx > -1) {
                this.items.splice(idx, 1, person)
                return
            }
            this.items.push(person)
        },
        removeItem(person) {
            const idx = this.items.findIndex(p => p.id === person.id)
            if (idx > -1) {
                this.items.splice(idx, 1)
            }
        },
        clearItems() {
            this.items = []
        },
        setLastFetch() {
            this.lastFetch = new Date()
        },
        setLastParams(params) {
            this.lastParams = params
        },
        setCurrentItemIndex(item) {
            const idx = this.items.findIndex(i => i.uuid === item.uuid)
            if (idx > -1) {
                this.currentItemIdx = idx
            }
        },
        clearCurrentItem() {
            this.currentItemIdx = null
        },
        async all(params) {
            this.getAll({ params })
        },
        async getAll({ params }) {
            this.setLastParams(params)
            await api.get(baseUrl + queryStringFromParams(params))
                .then(response => {
                    response.data.data.forEach(item => {
                        this.addItem(item)
                        this.setLastFetch(new Date())
                    })
                })
        },
        async getPeopleSinceLastFetch(params = null) {
            if (params === null) {
                params = this.lastParams
            }
            if (typeof params === 'undefined' || params === null) {
                params = {}
            }
            if (!params.where) {
                params.where = {}
            }
            params.where.since = this.lastFetch.toISOString()

            await api.get(baseUrl + queryStringFromParams(params))
                .then(response => {
                    response.data.forEach(item => {
                        this.addItem(item)
                        this.setLastFetch(new Date())
                    })
                })
        },
        getPerson({ uuid, params }) {
            return api.get(`${baseUrl}/${uuid}${queryStringFromParams(params)}`)
                .then(response => {
                    this.addItem(response.data.data)
                    this.setCurrentItemIndex(response.data.data)
                    return response
                })
        },
        async updateAttributes({ uuid, attributes }) {
            await api.put(`${baseUrl}/${uuid}`, attributes)
                .then(response => {
                    this.addItem(response.data)
                })
        },
        async updateProfile({ uuid, attributes }) {
            return api.put(`${baseUrl}/${uuid}/profile`, attributes)
                .then(response => {
                    this.addItem(response.data)
                    return response
                })
        },
        getMail(person) {
            if (person.uuid === null) return false
            return api.get(`/api/people/${person.uuid}/email`)
                .then(response => {
                    person.mailLog = response.data
                    return response
                })
        },
        deletePerson(person) {
            return api.delete(`/api/people/${person.uuid}`)
                .then(response => {
                    this.removeItem(person)
                    return response
                })
        },
        mergePeople({ authority, obsolete }) {
            return api.put('/api/people/merge', { authority_id: authority.id, obsolete_id: obsolete.id })
                .then(response => {
                    this.addItem(response.data)
                    this.removeItem(obsolete)
                })
        },
    },
})
