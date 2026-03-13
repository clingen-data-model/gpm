import { defineStore } from 'pinia'
import api from '@/http/api'

export function createCrudStore(id, config = {}) {
    if (!config.baseUrl) {
        throw new Error('createCrudStore: config.baseUrl must be set.')
    }

    return defineStore(id, {
        state: () => ({
            items: [],
            currentItemIdx: null,
        }),
        getters: {
            items: state => state.items,
            getItems: state => state.items,
            getItemById: state => itemId => state.items.find(item => item.id === itemId),
            currentItem: state => {
                if (state.currentItemIdx === null) {
                    if (config.modelClass) {
                        // eslint-disable-next-line new-cap
                        return new config.modelClass()
                    }
                    return {}
                }
                return state.items[state.currentItemIdx]
            },
        },
        actions: {
            addItem(item) {
                if (config.modelClass) {
                    // eslint-disable-next-line new-cap
                    item = new config.modelClass(item)
                }
                const idx = this.items.findIndex(i => i.id === item.id)
                if (idx > -1) {
                    this.items.splice(idx, 1, item)
                    return
                }
                this.items.push(item)
            },
            clearItems() {
                this.items = []
            },
            setCurrentItemIndex(item) {
                const idx = this.items.findIndex(i => i.uuid === item.uuid)
                this.currentItemIdx = idx
            },
            async getItems() {
                const items = await api.get(config.baseUrl).then(r => r.data)
                for (const i in items) {
                    this.addItem(items[i])
                }
            },
            ...(config.actions || {}),
        },
    })
}
