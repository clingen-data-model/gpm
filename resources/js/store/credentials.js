import {api} from '@/http';

const baseUrl = '/api/credentials';

export default {
    namespaced: true,
    state: () => ({
        items: [],
        currentItemIdx: null,
    }),
    getters: {
        items: state => state.items,
    },
    mutations: {
        addItem (state, item) {
            if (!item.id) {
                throw new Error('404: Item must have an id');
            }
            const idx = state.items.findIndex(i => i.id == item.id);
            if (idx > -1) {
                state.items.splice(idx, 1, item)
                return;
            }

            state.items.push(item);
        },
        setItems (state, items) {
            state.items = items;
        },

        removeItem(state, item) {
            const idx = state.items.findIndex(i => i.id == item.id);
            state.items.splice(idx, 1);
        },

    },
    actions: {
        getItems ({commit}) {
            return api.get(`${baseUrl}?withCount[]=people`)
                    .then(rsp => {
                        commit('setItems', rsp.data);
                        return rsp.data
                    });
        },
        create ({commit}, data) {
            return api.post(baseUrl, data)
                .then(rsp => {
                    commit('addItem', rsp.data);
                    return rsp;
                });
        },
        update ({commit}, updatedItem) {
            return api.put(`${baseUrl}/${updatedItem.id}`, updatedItem)
                .then(rsp => {
                    console.log({rsp});
                    commit('addItem', rsp.data);
                    return rsp;
                })
        },
        merge ({commit}, {authority, obsolete}) {
            return api.put(
                `/api/credentials/merge`,
                {
                    authority_id: authority.id,
                    obsolete_id: obsolete.id
                }
            ).then(rsp => {
                commit('removeItem', obsolete);
                commit('addItem', rsp.data);
                return rsp;
            })
        },
        delete ({commit}, item) {
            return api.delete(`/api/credentials/${item.id}`)
                .then(rsp => {
                    commit('removeItem', item)
                    return rsp;
                });
        }

    }
}
