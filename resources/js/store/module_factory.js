import api from '@/http/api'

const validateConfig = (config) => {
    if (!config.baseUrl) {
        throw new Error('Store module config baseUrl must be set.');
    }
}

export default function (config = {}) {
    validateConfig(config)
    const defaultObject = {
        namespaced: Boolean(config.namespace),
        state: {
            items: [],
            currentItemIdx: null,
        },
        getters: {
            items: (state) => {
                return state.items
            },
            getItems: (state) => {
                return state.items
            },
            getItemById: (state) => (id) => {
                return state.items.find(item => item.id == id);
            },
            currentItem: state => {
                if (state.currentItemIdx === null) {
                    if (config.modelClass) {
                        return new config.modelClass()
                    }
                    return {};
                }

                return state.items[state.currentItemIdx]
            }
        },
        mutations: {
            addItem(state, item) {
                if (config.modelClass) {
                    item = new config.modelClass(item)
                }

                const idx = state.items.findIndex(i => i.id == item.id);
                if (idx > -1) {
                    state.items.splice(idx, 1, item)
                    return;
                }

                state.items.push(item);
            },
            clearItems(state) {
                state.items = [];
            },
            setCurrentItemIndex(state, item) {
                const idx = state.items.findIndex(i => i.uuid == item.uuid);
                state.currentItemIdx = idx;
            },
            ...config.mutations
        },
        actions: {
            async getItems({commit}) {
                const items = await api.get(config.baseUrl)
                    .then(response => response.data);

                    for(const i in items) {
                        commit('addItem', items[i])
                    }

            },

            ...config.actions
        }
    }

    return defaultObject;
}
