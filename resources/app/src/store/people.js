import api from '@/http/api';
import Person from '@/domain/person'
import { v4 as uuid4 } from 'uuid';
import queryStringFromParams from '@/http/query_string_from_params'

const baseUrl = '/api/people';

export const state = () => ({
    requests: [],
    items: [],
    lastFetch: null,
    currentItemIdx: null
})

export const getters = {
    people: state => state.items,
    all: state => state.items,
    personWithUuid: (state, uuid) => state.items.find(i => i.uuid == uuid),
    getPersonWithUuid: (state, uuid) => state.items.find(i => i.uuid == uuid),
    indexForPersonWithUuid: (state, uuid) => state.items.findIndex(i => i.uuid == uuid),
    currentItem: state => {
        if (state.currentItemIdx === null) {
            return new Person();
        }

        return state.items[state.currentItemIdx]
    },
};

export const mutations = {
    addPerson(state, itemData) {
        // let person = itemData;
        // if (itemData.constructor.name == 'Object') {
            const person = new Person(itemData);
        // }
        const idx = state.items.findIndex(item => item.uuid == itemData.uuid);
        if (idx > -1) {
            state.items.splice(idx, 1, person)
            return;
        }

        state.items.push(person);
    },
    addItem(state, itemData) {
        this.addPerson(state, itemData)
    },
    clearItems(state) {
        state.items = [];
    },
    setLastFetch(state) {
        state.lastFetch = new Date();
    },
    setLastParams(state, params) {
        state.lastParams = params;
    },
    setCurrentItemIdx(state, item) {
        const idx = state.items.findIndex(i => i.uuid == item.uuid);
        state.currentItemIdx = idx;
    },
    setCurrentItemIndex(state, item) {
        const idx = state.items.findIndex(i => i.uuid == item.uuid);
        state.currentItemIdx = idx;
    },
};

export const actions = {
    // eslint-disable-next-line
    async all({ dispatch }, params) {
        dispatch('getAll', {params})
    },
    async getAll({ commit, state, dispatch }, {params, fresh = false}) {
        if (fresh || state.lastFetch === null) {
            commit('setLastParams', params);
            await api.get(baseUrl+queryStringFromParams(params))
                .then(response => {
                    response.data.forEach(item => {
                        commit('addPerson', item)
                        commit('setLastFetch', new Date())
                    })
                });
            return;
        }
        dispatch('getPeopleSinceLastFetch', params);
    },

    async getPeopleSinceLastFetch({ commit, state }, params=null) 
    {
        if (params === null) {
            params = state.lastParams
        }

        if (typeof params == 'undefined' || params === null) {
            params = {};
        }
        
        if(!params.where) {
                params.where = {};
        }

        params.where.since = state.lastFetch.toISOString()
        

        await api.get(baseUrl+ queryStringFromParams(params))
            .then(response => {
                response.data.forEach(item => {
                    commit('addPerson', item)
                    commit('setLastFetch', new Date)
                })
            })
    },

    async createPerson({ commit }, personData) {
        if (!personData.uuid) {
            personData.uuid = uuid4()
        }

        await api.post(baseUrl, personData)
            .then(response => {
                commit('addPerson', response.data);
                return response.data;
            });
    },

    async getPerson({ commit }, {uuid, params}) {
        await api.get(`${baseUrl}/${uuid}`+queryStringFromParams(params))
            .then(response => {
                commit('addPerson', response.data)
                commit('setCurrentItemIdx', response.data)
            });
    },

    async updateAttributes({ commit }, {uuid, attributes}) {
        await api.put(`${baseUrl}/${uuid}`, attributes)
            .then(response => {
                commit('addPerson', response.data);
            })
    },

    async updateProfile({ commit }, {uuid, attributes}) {
        await api.put(`${baseUrl}/${uuid}/profile`, attributes)
            .then(response => {
                commit('addPerson', response.data);
            })
    }

};

export default {
    namespaced: true,
    state: state,
    getters: getters,
    mutations: mutations,
    actions: actions
}