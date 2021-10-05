import Group from '@/domain/group';
import api from '@/http/api';
// import { v4 as uuid4 } from 'uuid';
import queryStringFromParams from "../http/query_string_from_params";

import bobsBurgers from '@/data/bobs_burgers'
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
        return state.items.find(app => app.uuid == uuid);
    },
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

    addMemberToGroup(state, groupUuid, member) {
        const group = getters.itemByUuid(state)(groupUuid);
        group.addMember(member);

        this.addItem(state, group);
    },

    setCurrentItemIdxByUuid(state, groupUuid) {
        const currentItemIndex = state.items.findIndex(i => {
            return i.uuid == groupUuid
        });
        if (currentItemIndex > -1) {
            state.currentItemIdx = currentItemIndex;
            return;
        }
        throw new Error(`Item with uuid ${groupUuid} not found in groups.items`);
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
                    commit('addItem', response.data);
                    return response;
                })
        
    },

    async memberAdd ({commit}, {uuid, personId, roleIds}) {
        const url = `${baseUrl}/${uuid}/members`
        // console.log('groups/memberAdd', {
        //     url,
        //     personId,
        //     roleIds
        // });
        // return {data: {id: 10}};
        return await api.post(url, {
                person_id: personId,
                roleIds: roleIds
            })
            .then(response =>  {
                commit('addMemberToGroup', response.data);
                return response.data;
            });
    },

    async memberInvite ({ commit }, {uuid, firstName, lastName, email, roleIds}) {
        const url = `${baseUrl}/${uuid}/invites`;
        // console.log('groups/memberInvite', {
        //     url,
        //     firstName,
        //     lastName,
        //     email,
        //     roleIds
        // });
        // return;
        const data = {
            first_name: firstName,
            last_name: lastName,
            email: email,
            role_ids: roleIds || null
        };
        return await api.post(url, data)
        .then(response => {
            commit('addMemberToGroup', response.data);
            return response.data;
        });
    },

    async memberAssignRole ( {commit}, {uuid, memberId, roleIds}) {
        const url= `${baseUrl}/${uuid}/members/${memberId}/roles`
        // console.log('groups/memberAssignRole', {
        //     url,
        //     uuid,
        //     memberId,
        //     roleIds,
        // });
        // return;
        return await api.post(url, {role_ids: roleIds})
            .then(response => {
                commit('addMemberToGroup', response.data);
                return response.data
            });
    },

    async memberRemoveRole ({ commit }, {uuid, memberId, roleId}) {
        const url = `${baseUrl}/${uuid}/members/${memberId}/roles/${roleId}`
        // console.log('groups/membeRemoveRole', {
        //     url,
        //     uuid,
        //     memberId,
        //     roleId,
        // });
        // return;
        return await api.delete(url)
                .then(response => {
                    commit('addMemberToGroup', response.data);
                    return response;
                });
    },

    async memberGrantPermission ({ commit }, {uuid, memberId, permissionIds}) {
        const url = `${baseUrl}/${uuid}/members/${memberId}/permissions`;
        console.log('groups/memberGrantPermission', {
            url,
            uuid,
            memberId,
            permissionIds,
        });
        return;
        return await api.post(url, {permission_ids: permissionIds})
            .then(response => {
                commit('addMemberToGroup', response.data);
                return response;
            });
    },

    async memberRevokePermission ({ commit }, {uuid, memberId, permissionId}) {
        const url = `${baseUrl}/${uuid}/members/${memberId}/permissions/${permissionId}`;
        console.log('groups/memberRevokePermission', {
            url,
            uuid,
            memberId,
            permissionId,
        });
        return;
        return await api.delete(url)
            .then(response => {
                commit('addMemberToGroup', response.data);
                return response;
            });
    },

    async memberRetire ({ commit }, {uuid, memberId, startDate, endDate}) {
        const url = `${baseUrl}/${uuid}/members/${memberId}/retire`;
        return await api.post(url, { start_date: startDate, end_date: endDate })
        .then(response => {
            commit('addMemberToGroup', response.data);
            return response;
        });
    },

    async memberRemove ({ commit }, {uuid, memberId, endDate}) {
        const url = `${baseUrl}/${uuid}/members/${memberId}`;
        return await api.delete(url, { end_date: endDate })
        .then(response => {
            commit('addMemberToGroup', response.data);
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