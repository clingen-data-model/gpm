import Group from '@/domain/group';
import api from '@/http/api';
// import { v4 as uuid4 } from 'uuid';
import queryStringFromParams from "../http/query_string_from_params";

const baseUrl = '/api/groups';

export const getters = {
    groups: state => state.items,
    items: state => state.items,
    all: state => state.items,
    requestCount: state => {
        return state.requests.length;
    },
    hasPendingRequests: state => {
        return state.requests.length > 0;
    },
    eps: state => {
        return state.items.filter(item => item.group_type_id == 3);
    },
    cdwgs: state => {
        return state.items.filter(item => item.group_type_id == 2);
    },
    wgs: state => {
        return state.items.filter(item => item.group_type_id == 1);
    },
    currentItem: state => {
        if (state.currentItemIdx === null) {
            return new Group();
        }

        return state.items[state.currentItemIdx]
    },
    getItemByUuid: (state) => (uuid) => {
        const item = state.items.find(i => i.uuid == uuid);
        return item;
    },
    getItemById: (state) => (id) => {
        const item = state.items.find(i => i.id == id);
        return item;
    }
};

export const mutations = {
    addItem (state, item) {
        const group = new Group(item);
        const idx = state.items.findIndex(i => i.id == item.id);
        if (idx > -1) {
            state.items.splice(idx, 1, group)
            return;
        }

        state.items.push(group);
    },

    setCurrentItemIdx(state, item) {
        const idx = state.items.findIndex(i => i.uuid == item.uuid);
        state.currentItemIdx = idx;
    },
    
    removeItem(state, item) {
        const idx = state.items.findIndex(i => i.uuid == item.uuid);
        state.items.splice(idx, 1);           
    },

    clearCurrentItemIdx(state) {
        state.currentItemIdx = null;
    },

    addMemberToGroup(state, member) {
        const group = getters.getItemById(state)(member.group_id);
        if (!group) {
            throw new Error('could not find group with id '+member.group_id+' in items.');
        }
        group.addMember(member);
    },

    removeMember(state, member) {
        const group = getters.getItemById(state)(member.group_id);
        if (!group) {
            throw new Error('could not find group with id '+member.group_id+' in items.');
        }
        group.removeMember(member);
    },

    setCurrentItemIdxByUuid(state, groupUuid) {
        if (state.items.length > 0) {
            const currentItemIndex = state.items.findIndex(i => {
                return i.uuid == groupUuid
            });
            if (currentItemIndex > -1) {
                state.currentItemIdx = currentItemIndex;
                return;
            }
            throw new Error(`Item with uuid ${groupUuid} not found in groups.items with length ${state.items.length}`);
        }
    }
};

export const actions = {
    async getItems ({commit}, params) {
        const url = baseUrl + queryStringFromParams(params);
        const data = await api.get(url)
            .then(response => response.data)

        data.forEach(item => {
            commit('addItem', item)
        })
    },

    async find ({commit}, uuid) {
        const url = `${baseUrl}/${uuid}${queryStringFromParams({ with: [] })}`;
        return await api.get(url)
                .then(response => {
                    commit('addItem', response.data.data);
                    return response;
                })
        
    },

    async memberAdd ({commit}, {uuid, personId, roleIds}) {
        const url = `${baseUrl}/${uuid}/members`
        
        return await api.post(url, {
                person_id: personId,
                role_ids: roleIds
            })
            .then(response =>  {
                commit('addMemberToGroup', response.data.data);
                return response.data.data;
            });
    },

    async memberInvite ({ commit }, {uuid, firstName, lastName, email, roleIds}) {
        const url = `${baseUrl}/${uuid}/invites`;
        const data = {
            first_name: firstName,
            last_name: lastName,
            email: email,
            role_ids: roleIds || null,
        };
        return await api.post(url, data)
        .then(response => {
            commit('addMemberToGroup', response.data.data);
            return response.data;
        });
    },

    async memberAssignRole ( {commit}, {uuid, memberId, roleIds}) {
        const url= `${baseUrl}/${uuid}/members/${memberId}/roles`
        return await api.post(url, {role_ids: roleIds})
            .then(response => {
                commit('addMemberToGroup', response.data.data);
                return response.data
            });
    },

    async memberRemoveRole ({ commit }, {uuid, memberId, roleId}) {
        const url = `${baseUrl}/${uuid}/members/${memberId}/roles/${roleId}`
        return await api.delete(url)
                .then(response => {
                    commit('addMemberToGroup', response.data.data);
                    return response;
                });
    },

    async memberGrantPermission ({ commit }, {uuid, memberId, permissionIds}) {
        const url = `${baseUrl}/${uuid}/members/${memberId}/permissions`;
        return await api.post(url, {permission_ids: permissionIds})
            .then(response => {
                commit('addMemberToGroup', response.data.data);
                return response;
            });
    },

    async memberRevokePermission ({ commit }, {uuid, memberId, permissionId}) {
        const url = `${baseUrl}/${uuid}/members/${memberId}/permissions/${permissionId}`;
        return await api.delete(url)
            .then(response => {
                commit('addMemberToGroup', response.data.data);
                return response;
            });
    },

    async memberRetire ({ commit }, {uuid, memberId, startDate, endDate}) {
        const url = `${baseUrl}/${uuid}/members/${memberId}/retire`;
        return await api.post(url, { start_date: startDate, end_date: endDate })
        .then(response => {
            commit('addMemberToGroup', response.data.data);
            return response;
        });
    },

    async memberRemove ({ commit }, {uuid, memberId, endDate}) {
        const url = `${baseUrl}/${uuid}/members/${memberId}`;
         console.log({uuid, memberId, endDate})
        return await api.delete(url, {data: { end_date: endDate }})
        .then(response => {
            commit('removeMember', response.data.data);
            return response;
        });
    },
};

export default {
    namespaced: true,
    state: () => ({
        requests: [],
        items: [],
        currentItemIdx: null,
    }),
    getters: getters,
    mutations: mutations,
    actions: actions
}