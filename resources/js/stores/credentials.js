import { defineStore } from 'pinia'
import { api } from '@/http'

const baseUrl = '/api/credentials'

export const useCredentialsStore = defineStore('credentials', {
    state: () => ({
        items: [],
        currentItemIdx: null,
    }),
    getters: {
        items: state => state.items,
    },
    actions: {
        addItem(item) {
            if (!item.id) {
                throw new Error('404: Item must have an id')
            }
            const idx = this.items.findIndex(i => i.id === item.id)
            if (idx > -1) {
                this.items.splice(idx, 1, item)
                return
            }
            this.items.push(item)
        },
        setItems(items) {
            this.items = items
        },
        removeItem(item) {
            const idx = this.items.findIndex(i => i.id === item.id)
            this.items.splice(idx, 1)
        },
        getItems() {
            return api.get(`${baseUrl}?withCount[]=people`)
                .then(rsp => {
                    this.setItems(rsp.data)
                    return rsp.data
                })
        },
        create(data) {
            return api.post(baseUrl, data)
                .then(rsp => {
                    this.addItem(rsp.data)
                    return rsp
                })
        },
        update(updatedItem) {
            return api.put(`${baseUrl}/${updatedItem.id}`, updatedItem)
                .then(rsp => {
                    // eslint-disable-next-line no-console
                    console.log({ rsp })
                    this.addItem(rsp.data)
                    return rsp
                })
        },
        merge({ authority, obsolete }) {
            return api.put('/api/credentials/merge', {
                authority_id: authority.id,
                obsolete_id: obsolete.id,
            }).then(rsp => {
                this.removeItem(obsolete)
                this.addItem(rsp.data)
                return rsp
            })
        },
        delete(item) {
            return api.delete(`/api/credentials/${item.id}`)
                .then(rsp => {
                    this.removeItem(item)
                    return rsp
                })
        },
    },
})
