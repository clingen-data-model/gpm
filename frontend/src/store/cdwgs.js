// import store from ".";
import axios from 'axios'

export default {
    state: () => ({
        items: []
    }),
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
        async getCdwgs({ commit }) {
            await axios.get('/api/cdwgs')
                .then(response => {
                    response.data.forEach(item => {
                        commit('addItem', item);
                    })
                })
        }
    }
}