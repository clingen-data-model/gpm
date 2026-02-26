import { v4 as uuid4 } from 'uuid';
import Group from '@/domain/group';
import { api, queryStringFromParams } from '@/http';
import { clone } from 'lodash-es';

const baseUrl = '/api/groups';
const getApplicationUrl = (uuid) => `${baseUrl}/${uuid}/expert-panel`;

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
        return state.items.filter(item => item.group_type_id === 3);
    },
    cdwgs: state => {
        return state.items.filter(item => item.group_type_id === 2);
    },
    sccdwgs: state => {
        return state.items.filter(item => item.group_type_id === 6);
    },
    wgs: state => {
        return state.items.filter(item => item.group_type_id === 1);
    },
    currentItem: state => {
        const item = state.items[state.currentItemIdx]
        return item;
    },
    currentItemOrNew: state => {
        const item = state.items[state.currentItemIdx] || new Group();
        return item;
    },
    getItemByUuid: (state) => (uuid) => {
        const item = state.items.find(i => i.uuid === uuid);
        return item;
    },
    getItemById: (state) => (id) => {
        const item = state.items.find(i => i.id === id);
        return item;
    }
};

export const mutations = {
    addItem (state, item) {
        if (!item.id) {
            throw new Error('404: group item id is required')
        }
        const group = Object.prototype.hasOwnProperty.call(item, 'attributes')
                        ? item : new Group(item);
        const idx = state.items.findIndex(i => i.id === item.id);
        if (idx > -1) {
            state.items.splice(idx, 1, group)
            return;
        }

        state.items.push(group);
    },

    setCurrentItemIndex(state, item) {
        const idx = state.items.findIndex(i => i.uuid === item.uuid);
        state.currentItemIdx = idx;
    },

    removeItem(state, item) {
        const idx = state.items.findIndex(i => i.uuid === item.uuid);
        state.items.splice(idx, 1);
    },

    clearCurrentItemIdx(state) {
        state.currentItemIdx = null;
    },

    clearCurrentItem(state) {
        state.currentItemIdx = null;
    },

    addMemberToGroup(state, member) {
        const group = getters.getItemById(state)(member.group_id);
        if (!group) {
            throw new Error(`could not find group with id ${member.group_id} in items.`);
        }
        group.addMember(member);
    },

    removeMember(state, member) {
        const group = getters.getItemById(state)(member.group_id);
        if (!group) {
            throw new Error(`could not find group with id ${member.group_id} in items.`);
        }
        group.removeMember(member);
    },

    setCurrentItemIndexByUuid(state, groupUuid) {
        if (state.items.length > 0) {
            const currentItemIndex = state.items.findIndex(i => {
                return i.uuid === groupUuid
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
    async create ({commit}, groupData) {
        return await api.post(baseUrl, groupData)
            .then(response => {
                commit('addItem', response.data.data);
                return response;
            });
    },

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

    delete ({commit, getters}, uuid) {
        return api.delete(`${baseUrl}/${uuid}`)
            .then(() => {
                const item = getters.getItemByUuid(uuid);
                commit('removeItem', item);
            });
    },

    findAndSetCurrent ({dispatch, commit}, uuid) {
        return dispatch('find', uuid)
            .then(response => {
                commit('setCurrentItemIndexByUuid', uuid)
                return response;
            })
    },

    async memberAdd ({commit}, {uuid, personId, roleIds, data}) {
        const url = `${baseUrl}/${uuid}/members`

        return await api.post(url, {
                person_id: personId,
                role_ids: roleIds,
                ...data
            })
            .then(response =>  {
                commit('addMemberToGroup', response.data.data);
                return response.data.data;
            });
    },

    async getMembers ({commit}, group) {
        const members = await api.get(`${baseUrl}/${group.uuid}/members`)
                            .then(response => response.data.data);

        members.forEach(member => {
            commit('addMemberToGroup', member);
        });
    },

    async memberInvite ({ commit }, {uuid, data}) {
        const url = `${baseUrl}/${uuid}/invites`;
        const memberData = {
            first_name: data.firstName,
            last_name: data.lastName,
            email: data.email,
            role_ids: data.roleIds || null,
            ...data
        };
        return await api.post(url, memberData)
            .then(response => {
                commit('addMemberToGroup', response.data.data);
                return response.data;
            });
    },

    async memberUpdate ( {commit}, {groupUuid, memberId, data}) {
        const url= `${baseUrl}/${groupUuid}/members/${memberId}`;
        return await api.put(url, data)
                .then(response => {
                    commit('addMemberToGroup', response.data.data);
                    return response.data
                })
    },

    async memberAssignRole ( context, {uuid, memberId, roleIds}) {
        const url= `${baseUrl}/${uuid}/members/${memberId}/roles`
        return await api.post(url, {role_ids: roleIds})
            .then(response => {
                context.getters.getItemByUuid(uuid).findMember(memberId)
                    .addRoles(roleIds);
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

    async memberSyncRoles ({ dispatch }, {group, member}) {
        const promises = [];
        if (member.addedRoles.length > 0) {
            promises.push(dispatch(
                'memberAssignRole',
                {uuid: group.uuid, memberId: member.id, roleIds: member.addedRoles.map(role => role.id)}
            ));
        }

        member.removedRoles.forEach(role => {
            promises.push(dispatch(
                'memberRemoveRole',
                {uuid: group.uuid, memberId: member.id, roleId: role.id}
            ));
        })

        return promises;
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

    async memberUnretire ({ commit }, {uuid, memberId}) {
        const url = `${baseUrl}/${uuid}/members/${memberId}/unretire`;
        return await api.post(url)
        .then(response => {
            commit('addMemberToGroup', response.data.data);
            return response;
        });
    },

    async memberRemove ({ commit }, {uuid, memberId, endDate}) {
        const url = `${baseUrl}/${uuid}/members/${memberId}`;
        return await api.delete(url, {data: { end_date: endDate }})
        .then(response => {
            commit('removeMember', response.data.data);
            return response;
        });
    },

    async descriptionUpdate({ commit, getters }, {uuid, description}) {

        return await api.put(
            `${baseUrl}/${uuid}`,
            { description }
        )
        .then(response => {
            const item = getters.getItemByUuid(uuid);
            item.description = response.data.description;
            commit('addItem', item)
            return response;
        })
    },

    async membershipDescriptionUpdate({commit, getters}, {uuid, membershipDescription}) {
        return await api.put(
            `${getApplicationUrl(uuid)}/membership-description`,
            { membership_description: membershipDescription }
        )
        .then(response => {
            const item = getters.getItemByUuid(uuid);
            item.memberDescription = response.data.data.expert_panel.memberDescription;
            commit('addItem', item)
            return response;
        })
    },

    async scopeDescriptionUpdate({ commit, getters }, {uuid, scopeDescription}) {

        return await api.put(
            `${getApplicationUrl(uuid)}/scope-description`,
            { scope_description: scopeDescription }
        )
        .then(response => {
            const item = getters.getItemByUuid(uuid);
            item.memberDescription = response.data.data.expert_panel.scopeDescription;
            commit('addItem', item)
            return response;
        })
    },

    // eslint-disable-next-line unused-imports/no-unused-vars
    async curationReviewProtocolUpdate({ commit }, {uuid, expertPanel}) {
        if (expertPanel.curation_review_protocol_id !== 100) {
            expertPanel.curation_review_protocol_other = null;
        }

        return api.put(`${getApplicationUrl(uuid)}/curation-review-protocols`, expertPanel.attributes)
    },

    getSpecifications({ commit }, group) {
        if (!group.is_vcep_or_scvcep) {
            throw new Error(
                "Can not retreive specfications. Only VCEPS have specifications."
            );
        }

        return api.get(`${baseUrl}/${group.uuid}/specifications`)
            .then(rsp => {
                group.expert_panel.specifications = rsp.data
                commit('addItem', group);
            })
    },

    getGenes ({ commit, getters,}, group) {
        return api.get(`${getApplicationUrl(group.uuid)}/genes`)
            .then(response => {
                const item = getters.getItemByUuid(group.uuid)
                item.expert_panel.genes = response.data;
                commit('addItem', item);
                return response.data;
            });
    },

    getEvidenceSummaries ({commit, getters}, group) {
        return api.get(`${getApplicationUrl(group.uuid)}/evidence-summaries`)
            .then(response => {
                const item = getters.getItemByUuid(group.uuid)
                item.expert_panel.evidence_summaries = response.data.data;
                commit('addItem', item);
                return response.data.data
            })
    },

    // eslint-disable-next-line unused-imports/no-unused-vars
    saveApplicationData ({commit}, group) {
        // delete group b/c it's not needed for the request and interferes
        // with laravel's route-model binding.
        const expertPanelAttributes = clone(group.expert_panel.attributes);
        delete(expertPanelAttributes.group);

        return api.put(`${baseUrl}/${group.uuid}/application`, expertPanelAttributes)
            .then(response => {
                return response;
            });
    },

    submitApplicationStep ({commit}, {group, notes}) {
        return api.post(`${baseUrl}/${group.uuid}/application/submission`, {notes})
            .then(response => {
                group.expert_panel.submissions.push(response.data);
                commit('addItem', group);
                return response;
            })
    },

    getSubmissions ({commit}, group) {
        return api.get(`${baseUrl}/${group.uuid}/application/submission`)
            .then(response => {
                group.expert_panel.submissions = response.data;
                commit('addItem', group);
                return response;
            })
    },

    getNextActions( {commit}, group) {
        return api.get(`${baseUrl}/${group.uuid}/next-actions`)
            .then( response => {
                group.expert_panel.nextActions = response.data;
                commit('addItem', group);
                return response;
            });
    },

    getDocuments ({commit}, group) {
        return api.get(`${baseUrl}/${group.uuid}/documents`)
            .then(response  => {
                // Attach documents to expert_panel for backwards compatibility - 2021-12-18
                group.expert_panel.documents = response.data.filter(doc => doc.document_type_id < 8);
                // Attach documents to group for forward compatibility - 2021-12-18
                group.documents = response.data;
                commit('addItem', group);
                return response;
            });
    },

    addApplicationDocument ({commit}, {group, data}) {
        if (!data.has('uuid')) {
            data.append('uuid', uuid4())
        }

        return api.postForm(`/api/applications/${group.expert_panel.uuid}/documents`, data)
            .then(response => {
                group.documents.push(response.data);
                if (response.data.document_type_id < 8) {
                    group.expert_panel.documents.push(response.data);
                    commit('addItem', group);
                }
                return response.data;
            });
    },

    addDocument ({commit}, {group, data}) {
        if (!data.has('uuid')) {
            data.append('uuid', uuid4());
        }

        return api.postForm(`${baseUrl}/${group.uuid}/documents`, data)
                .then(response => {
                    group.documents.push(response.data);
                    if (response.data.document_type_id < 8) {
                        group.expert_panel.documents.push(response.data);
                        commit('addItem', group);
                    }
                    return response.data;
                });
    },

    // eslint-disable-next-line unused-imports/no-unused-vars
    async updateDocument({ commit }, { group, document }) {
        return api.putForm(`/api/groups/${group.uuid}/documents/${document.uuid}`, document)
            .then((response) => {
                const idx = group.documents.findIndex(doc => doc.id === document.id);
                group.documents[idx] = response.data;
                return response;
            });
    },

    // eslint-disable-next-line unused-imports/no-unused-vars
    async deleteDocument( { commit }, {group, document}) {
        await api.delete(`/api/applications/${group.expert_panel.uuid}/documents/${document.uuid}`)
            .then(response => {
                const docIdx = group.documents.indexOf(document);
                group.documents.splice(docIdx, 1);
                return response;
            });
    },


    // eslint-disable-next-line unused-imports/no-unused-vars
    async addLogEntry({ dispatch }, { group, logEntryData }) {
        const url = `/api/groups/${group.uuid}/log-entries`
        await api.post(url, logEntryData)
            .then(() => {
            })
    },

    getPendingTasks( {getters}, group) {
        const url = `${baseUrl}/${group.uuid}/tasks?pending`;
        return api.get(url)
            .then(response => {
                const groupInStore = getters.getItemByUuid(group.uuid);
                groupInStore.pendingTasks = response.data;
                return response;
            });
    },

    completeSustainedCurationReview( {getters}, group) {
        const url = `${baseUrl}/${group.uuid}/expert-panel/sustained-curation-reviews`;
        return api.put(url)
                .then(response => {
                    const groupInStore = getters.getItemByUuid(group.uuid);
                    const pendingTasks = groupInStore.pendingTasks || [];
                    response.data.forEach((task) => {
                        const pendingTaskIdx = pendingTasks.indexOf(pt => pt.id === task.id);
                        if (pendingTaskIdx > -1 && task.completed_at) {
                            delete(groupInStore.pendingTasks[pendingTaskIdx]);
                        }
                    });
                    return response;
                });
    },

    // eslint-disable-next-line unused-imports/no-unused-vars
    async updateApprovalDate({ commit }, { group, dateApproved, step }) {
        return api.put(`/api/applications/${group.expert_panel.uuid}/approve`, {
                date_approved: dateApproved,
                step
            })
            .then(() => {
                group.expert_panel.updateApprovalDate(dateApproved, step);
            });
    },

    getAnnualUpdate (context, group) {
        api.get(`/api/groups/${group.uuid}/expert-panel/annual-updates`, {headers: {'X-Ignore-Missing': 1} })
        .then(response => {
            group.expert_panel.annualUpdate = response.data
        });
    },


    createAnnualUpdateForLatestWindow(context, group) {
        return api.post(`/api/groups/${group.uuid}/expert-panel/annual-updates`)
            .then(response => {
                group.expert_panel.annualUpdate = response.data
            });
    },


    getChildren(context, group) {
        return api.get(`/api/groups/${group.uuid}/children`)
            .then(response => {
                group.children = response.data.data
            });
    },


    async approveCurrentStep({ dispatch }, { group, dateApproved, notifyContacts, subject, body, attachments }) {
        const formData = new FormData();
        formData.append('date_approved', dateApproved);
        formData.append('notify_contacts', notifyContacts);
        formData.append('subject', subject);
        formData.append('body', body);

        Array.from(attachments).forEach((file, idx) => {
            formData.append(`attachments[${  idx  }]`, file);
        });

        const url = `/api/applications/${group.uuid}/current-step/approve`
        return await api.postForm(
                url,
                formData,
            )
            .then(() => {
                dispatch('findAndSetCurrent', group.uuid);
            });
    },

    async checkpoints(_, { group_ids = [], queue = true, dry_run = false }) {
      const { data } = await api.post('/api/groups/checkpoints', { group_ids, queue, dry_run })
      return data; // { status, accepted, batch_id, ids, denied_ids, not_found_ids }
    }

};

export default {
    namespaced: true,
    state: () => ({
        requests: [],
        items: [],
        currentItemIdx: null,
    }),
    getters,
    mutations,
    actions
}
