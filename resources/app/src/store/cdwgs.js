// import store from ".";
import axios from '@/http/api'

export default {
    namespaced: true,
    state: () => ({
        items: []
    }),
    getters: {
        all: state => state.items
    },
    mutations: {
        addItem (state, item) {
            const idx = state.items.findIndex(i => i.id == item.id);
            if (idx > -1) {
                state.items.splice(idx, 1, item)
                return;
            }

            state.items.push(item);
        }
    },
    actions: {
        async getAll({ commit }) {
            await axios.get('/api/cdwgs')
                .then(response => {
                    response.data.forEach(item => {
                        commit('addItem', item);
                    })
                })
        }
    }
}