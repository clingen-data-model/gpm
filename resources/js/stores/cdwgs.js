import { defineStore } from 'pinia'
import axios from '@/http/api'

export const useCdwgsStore = defineStore('cdwgs', {
    state: () => ({
        items: [],
    }),
    getters: {},
    actions: {
        addItem(item) {
            const idx = this.items.findIndex(i => i.id === item.id)
            if (idx > -1) {
                this.items.splice(idx, 1, item)
                return
            }
            this.items.push(item)
        },
        async getAll() {
            const response = await axios.get('/api/cdwgs')
            response.data.forEach(item => this.addItem(item))
        },
    },
})
