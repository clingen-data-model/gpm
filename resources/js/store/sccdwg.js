import axios from '@/http/api'

export default {
	namespaced: true,
	state: () => ({
		items: []
	}),
	getters: {
		all: (state) => state.items
	},
	mutations: {
		setAll(state, payload) {
			state.items = payload || []
		}
	},
	actions: {
		async fetchAll({ commit, state }) {
			if (state.items.length) return		
			const resp = await axios.get('/api/cdwgs?scope=sc')
			commit('setAll', Array.isArray(resp.data) ? resp.data : (resp.data?.data || []))
		}
	}
}
