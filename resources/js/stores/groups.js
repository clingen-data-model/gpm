import { defineStore } from 'pinia'
import { v4 as uuid4 } from 'uuid'
import Group from '@/domain/group'
import { api, queryStringFromParams } from '@/http'
import { clone } from 'lodash-es'

const baseUrl = '/api/groups'
const getApplicationUrl = uuid => `${baseUrl}/${uuid}/expert-panel`

export const useGroupsStore = defineStore('groups', {
    state: () => ({
        requests: [],
        items: [],
        currentItemIdx: null,
    }),
    getters: {
        groups: state => state.items,
        requestCount: state => state.requests.length,
        hasPendingRequests: state => state.requests.length > 0,
        eps: state => state.items.filter(item => item.group_type_id === 3),
        cdwgs: state => state.items.filter(item => item.group_type_id === 2),
        wgs: state => state.items.filter(item => item.group_type_id === 1),
        currentItem: state => state.items[state.currentItemIdx],
        currentItemOrNew: state => state.items[state.currentItemIdx] || new Group(),
        getItemByUuid: state => uuid => state.items.find(i => i.uuid === uuid),
        getItemById: state => id => state.items.find(i => i.id === id),
    },
    actions: {
        addItem(item) {
            if (!item.id) {
                throw new Error('404: group item id is required')
            }
            const group = Object.prototype.hasOwnProperty.call(item, 'attributes')
                ? item : new Group(item)
            const idx = this.items.findIndex(i => i.id === item.id)
            if (idx > -1) {
                this.items.splice(idx, 1, group)
                return
            }
            this.items.push(group)
        },
        setCurrentItemIndex(item) {
            const idx = this.items.findIndex(i => i.uuid === item.uuid)
            this.currentItemIdx = idx
        },
        removeItem(item) {
            const idx = this.items.findIndex(i => i.uuid === item.uuid)
            this.items.splice(idx, 1)
        },
        clearCurrentItemIdx() {
            this.currentItemIdx = null
        },
        clearCurrentItem() {
            this.currentItemIdx = null
        },
        addMemberToGroup(member) {
            const group = this.getItemById(member.group_id)
            if (!group) {
                throw new Error(`could not find group with id ${member.group_id} in items.`)
            }
            group.addMember(member)
        },
        removeMember(member) {
            const group = this.getItemById(member.group_id)
            if (!group) {
                throw new Error(`could not find group with id ${member.group_id} in items.`)
            }
            group.removeMember(member)
        },
        setCurrentItemIndexByUuid(groupUuid) {
            if (this.items.length > 0) {
                const currentItemIndex = this.items.findIndex(i => i.uuid === groupUuid)
                if (currentItemIndex > -1) {
                    this.currentItemIdx = currentItemIndex
                    return
                }
                throw new Error(`Item with uuid ${groupUuid} not found in groups.items with length ${this.items.length}`)
            }
        },
        async create(groupData) {
            return await api.post(baseUrl, groupData)
                .then(response => {
                    this.addItem(response.data.data)
                    return response
                })
        },
        async getItems(params) {
            const url = baseUrl + queryStringFromParams(params)
            const data = await api.get(url).then(response => response.data)
            data.forEach(item => this.addItem(item))
        },
        async find(uuid) {
            const url = `${baseUrl}/${uuid}${queryStringFromParams({ with: [] })}`
            return await api.get(url)
                .then(response => {
                    this.addItem(response.data.data)
                    return response
                })
        },
        delete(uuid) {
            return api.delete(`${baseUrl}/${uuid}`)
                .then(() => {
                    const item = this.getItemByUuid(uuid)
                    this.removeItem(item)
                })
        },
        findAndSetCurrent(uuid) {
            return this.find(uuid)
                .then(response => {
                    this.setCurrentItemIndexByUuid(uuid)
                    return response
                })
        },
        async memberAdd({ uuid, personId, roleIds, data }) {
            const url = `${baseUrl}/${uuid}/members`
            return await api.post(url, {
                person_id: personId,
                role_ids: roleIds,
                ...data,
            })
            .then(response => {
                this.addMemberToGroup(response.data.data)
                return response.data.data
            })
        },
        async getMembers(group) {
            const members = await api.get(`${baseUrl}/${group.uuid}/members`)
                .then(response => response.data.data)
            members.forEach(member => this.addMemberToGroup(member))
        },
        async memberInvite({ uuid, data }) {
            const url = `${baseUrl}/${uuid}/invites`
            const memberData = {
                first_name: data.firstName,
                last_name: data.lastName,
                email: data.email,
                role_ids: data.roleIds || null,
                ...data,
            }
            return await api.post(url, memberData)
                .then(response => {
                    this.addMemberToGroup(response.data.data)
                    return response.data
                })
        },
        async memberUpdate({ groupUuid, memberId, data }) {
            const url = `${baseUrl}/${groupUuid}/members/${memberId}`
            return await api.put(url, data)
                .then(response => {
                    this.addMemberToGroup(response.data.data)
                    return response.data
                })
        },
        async memberAssignRole({ uuid, memberId, roleIds }) {
            const url = `${baseUrl}/${uuid}/members/${memberId}/roles`
            return await api.post(url, { role_ids: roleIds })
                .then(response => {
                    this.getItemByUuid(uuid).findMember(memberId).addRoles(roleIds)
                    return response.data
                })
        },
        async memberRemoveRole({ uuid, memberId, roleId }) {
            const url = `${baseUrl}/${uuid}/members/${memberId}/roles/${roleId}`
            return await api.delete(url)
                .then(response => {
                    this.addMemberToGroup(response.data.data)
                    return response
                })
        },
        async memberSyncRoles({ group, member }) {
            const promises = []
            if (member.addedRoles.length > 0) {
                promises.push(this.memberAssignRole({
                    uuid: group.uuid,
                    memberId: member.id,
                    roleIds: member.addedRoles.map(role => role.id),
                }))
            }
            member.removedRoles.forEach(role => {
                promises.push(this.memberRemoveRole({
                    uuid: group.uuid,
                    memberId: member.id,
                    roleId: role.id,
                }))
            })
            return promises
        },
        async memberGrantPermission({ uuid, memberId, permissionIds }) {
            const url = `${baseUrl}/${uuid}/members/${memberId}/permissions`
            return await api.post(url, { permission_ids: permissionIds })
                .then(response => {
                    this.addMemberToGroup(response.data.data)
                    return response
                })
        },
        async memberRevokePermission({ uuid, memberId, permissionId }) {
            const url = `${baseUrl}/${uuid}/members/${memberId}/permissions/${permissionId}`
            return await api.delete(url)
                .then(response => {
                    this.addMemberToGroup(response.data.data)
                    return response
                })
        },
        async memberRetire({ uuid, memberId, startDate, endDate }) {
            const url = `${baseUrl}/${uuid}/members/${memberId}/retire`
            return await api.post(url, { start_date: startDate, end_date: endDate })
                .then(response => {
                    this.addMemberToGroup(response.data.data)
                    return response
                })
        },
        async memberUnretire({ uuid, memberId }) {
            const url = `${baseUrl}/${uuid}/members/${memberId}/unretire`
            return await api.post(url)
                .then(response => {
                    this.addMemberToGroup(response.data.data)
                    return response
                })
        },
        async memberRemove({ uuid, memberId, endDate }) {
            const url = `${baseUrl}/${uuid}/members/${memberId}`
            return await api.delete(url, { data: { end_date: endDate } })
                .then(response => {
                    this.removeMember(response.data.data)
                    return response
                })
        },
        async descriptionUpdate({ uuid, description }) {
            return await api.put(`${baseUrl}/${uuid}`, { description })
                .then(response => {
                    const item = this.getItemByUuid(uuid)
                    item.description = response.data.description
                    this.addItem(item)
                    return response
                })
        },
        async membershipDescriptionUpdate({ uuid, membershipDescription }) {
            return await api.put(
                `${getApplicationUrl(uuid)}/membership-description`,
                { membership_description: membershipDescription },
            )
            .then(response => {
                const item = this.getItemByUuid(uuid)
                item.memberDescription = response.data.data.expert_panel.memberDescription
                this.addItem(item)
                return response
            })
        },
        async scopeDescriptionUpdate({ uuid, scopeDescription }) {
            return await api.put(
                `${getApplicationUrl(uuid)}/scope-description`,
                { scope_description: scopeDescription },
            )
            .then(response => {
                const item = this.getItemByUuid(uuid)
                item.memberDescription = response.data.data.expert_panel.scopeDescription
                this.addItem(item)
                return response
            })
        },
        async curationReviewProtocolUpdate({ uuid, expertPanel }) {
            if (expertPanel.curation_review_protocol_id !== 100) {
                expertPanel.curation_review_protocol_other = null
            }
            return api.put(`${getApplicationUrl(uuid)}/curation-review-protocols`, expertPanel.attributes)
        },
        getSpecifications(group) {
            if (!group.is_vcep_or_scvcep) {
                throw new Error('Can not retreive specfications. Only VCEPS have specifications.')
            }
            return api.get(`${baseUrl}/${group.uuid}/specifications`)
                .then(rsp => {
                    group.expert_panel.specifications = rsp.data
                    this.addItem(group)
                })
        },
        getGenes(group) {
            return api.get(`${getApplicationUrl(group.uuid)}/genes`)
                .then(response => {
                    const item = this.getItemByUuid(group.uuid)
                    item.expert_panel.genes = response.data
                    this.addItem(item)
                    return response.data
                })
        },
        getEvidenceSummaries(group) {
            return api.get(`${getApplicationUrl(group.uuid)}/evidence-summaries`)
                .then(response => {
                    const item = this.getItemByUuid(group.uuid)
                    item.expert_panel.evidence_summaries = response.data.data
                    this.addItem(item)
                    return response.data.data
                })
        },
        saveApplicationData(group) {
            const expertPanelAttributes = clone(group.expert_panel.attributes)
            delete expertPanelAttributes.group
            return api.put(`${baseUrl}/${group.uuid}/application`, expertPanelAttributes)
                .then(response => response)
        },
        submitApplicationStep({ group, notes }) {
            return api.post(`${baseUrl}/${group.uuid}/application/submission`, { notes })
                .then(response => {
                    group.expert_panel.submissions.push(response.data)
                    this.addItem(group)
                    return response
                })
        },
        getSubmissions(group) {
            return api.get(`${baseUrl}/${group.uuid}/application/submission`)
                .then(response => {
                    group.expert_panel.submissions = response.data
                    this.addItem(group)
                    return response
                })
        },
        getNextActions(group) {
            return api.get(`${baseUrl}/${group.uuid}/next-actions`)
                .then(response => {
                    group.expert_panel.nextActions = response.data
                    this.addItem(group)
                    return response
                })
        },
        getDocuments(group) {
            return api.get(`${baseUrl}/${group.uuid}/documents`)
                .then(response => {
                    group.expert_panel.documents = response.data.filter(doc => doc.document_type_id < 8)
                    group.documents = response.data
                    this.addItem(group)
                    return response
                })
        },
        addApplicationDocument({ group, data }) {
            if (!data.has('uuid')) {
                data.append('uuid', uuid4())
            }
            return api.postForm(`/api/applications/${group.expert_panel.uuid}/documents`, data)
                .then(response => {
                    group.documents.push(response.data)
                    if (response.data.document_type_id < 8) {
                        group.expert_panel.documents.push(response.data)
                        this.addItem(group)
                    }
                    return response.data
                })
        },
        addDocument({ group, data }) {
            if (!data.has('uuid')) {
                data.append('uuid', uuid4())
            }
            return api.postForm(`${baseUrl}/${group.uuid}/documents`, data)
                .then(response => {
                    group.documents.push(response.data)
                    if (response.data.document_type_id < 8) {
                        group.expert_panel.documents.push(response.data)
                        this.addItem(group)
                    }
                    return response.data
                })
        },
        async updateDocument({ group, document }) {
            return api.putForm(`/api/groups/${group.uuid}/documents/${document.uuid}`, document)
                .then(response => {
                    const idx = group.documents.findIndex(doc => doc.id === document.id)
                    group.documents[idx] = response.data
                    return response
                })
        },
        async deleteDocument({ group, document }) {
            await api.delete(`/api/applications/${group.expert_panel.uuid}/documents/${document.uuid}`)
                .then(response => {
                    const docIdx = group.documents.indexOf(document)
                    group.documents.splice(docIdx, 1)
                    return response
                })
        },
        async addLogEntry({ group, logEntryData }) {
            const url = `/api/groups/${group.uuid}/log-entries`
            await api.post(url, logEntryData)
        },
        getPendingTasks(group) {
            const url = `${baseUrl}/${group.uuid}/tasks?pending`
            return api.get(url)
                .then(response => {
                    const groupInStore = this.getItemByUuid(group.uuid)
                    groupInStore.pendingTasks = response.data
                    return response
                })
        },
        completeSustainedCurationReview(group) {
            const url = `${baseUrl}/${group.uuid}/expert-panel/sustained-curation-reviews`
            return api.put(url)
                .then(response => {
                    const groupInStore = this.getItemByUuid(group.uuid)
                    const pendingTasks = groupInStore.pendingTasks || []
                    response.data.forEach(task => {
                        const pendingTaskIdx = pendingTasks.indexOf(pt => pt.id === task.id)
                        if (pendingTaskIdx > -1 && task.completed_at) {
                            delete groupInStore.pendingTasks[pendingTaskIdx]
                        }
                    })
                    return response
                })
        },
        async updateApprovalDate({ group, dateApproved, step }) {
            return api.put(`/api/applications/${group.expert_panel.uuid}/approve`, {
                date_approved: dateApproved,
                step,
            })
            .then(() => {
                group.expert_panel.updateApprovalDate(dateApproved, step)
            })
        },
        getAnnualUpdate(group) {
            api.get(`/api/groups/${group.uuid}/expert-panel/annual-updates`, { headers: { 'X-Ignore-Missing': 1 } })
                .then(response => {
                    group.expert_panel.annualUpdate = response.data
                })
        },
        createAnnualUpdateForLatestWindow(group) {
            return api.post(`/api/groups/${group.uuid}/expert-panel/annual-updates`)
                .then(response => {
                    group.expert_panel.annualUpdate = response.data
                })
        },
        getChildren(group) {
            return api.get(`/api/groups/${group.uuid}/children`)
                .then(response => {
                    group.children = response.data.data
                })
        },
        async approveCurrentStep({ group, dateApproved, notifyContacts, subject, body, attachments }) {
            const formData = new FormData()
            formData.append('date_approved', dateApproved)
            formData.append('notify_contacts', notifyContacts)
            formData.append('subject', subject)
            formData.append('body', body)
            Array.from(attachments).forEach((file, idx) => {
                formData.append(`attachments[${idx}]`, file)
            })
            const url = `/api/applications/${group.uuid}/current-step/approve`
            return await api.postForm(url, formData)
                .then(() => {
                    this.findAndSetCurrent(group.uuid)
                })
        },
        async checkpoints({ group_ids = [], queue = true, dry_run = false }) {
            const { data } = await api.post('/api/groups/checkpoints', { group_ids, queue, dry_run })
            return data
        },
    },
})
