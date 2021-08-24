import Entity from './entity'
import configs from '@/configs'

class Application extends Entity {
    static dates = [
        'date_completed',
        'date_initiated',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    static defaults = {
        working_name: null,
        expert_panel_type_id: null,
        cdwg_id: null,
        id: null,
        uuid: null,
        name: null,
        documents: [],
        approval_dates: {},
        date_completed: null,
        date_initiated: null,
        cdwg: {},
        latest_log_entry: {},
        latest_pending_next_action: null,
        log_entries: [],
        type: {},
        contacts: []
    };

    get isCompleted() {
        if (this.attributes.date_completed) {
            return true;
        }

        return false;
    }

    get isGcep() {
        return this.expert_panel_type_id == 1;
    }

    get isVcep() {
        return this.expert_panel_type_id == 2;
    }

    get steps() {
        if (this.expert_panel_type_id == 1) {
            return [1];
        }
        if (this.expert_panel_type_id == 2) {
            return [1, 2, 3, 4];
        }

        throw new Error('Unknown expert_panel_type_id found when determining applicaiton steps.');
    }

    get pendingNextActions() {
        if (!this.next_actions) {
            return [];
        }
        return this.next_actions.filter(na => na.date_completed === null);
    }

    get pendingActionsByAssignee() {
        const naAssignees = Object.values(configs.nextActions.assignees).map(na => na.id);
        let groups = {};
        for (let i in naAssignees) {
            const item = naAssignees[i];
            groups[item] = [];
        }
        if (!this.next_actions) {
            return groups;
        }

        for (const na in this.pendingNextActions) {
            const assignedTo = this.pendingNextActions[na].assigned_to;
            groups[assignedTo].push(this.pendingNextActions[na]);
        }
        return groups
    }

    get hasContacts() {
        return (this.contacts.length > 0);
    }

    isWaitingOnCdwgOc() {
        return this.pendingActionsAssigneeCounts.cdwg_oc > 0;
    }

    isWaitingOnExpertPanel() {
        return this.pendingActionsAssigneeCounts.expert_panel > 0;
    }

    stepIsApproved(stepNumber) {
        if (!this.steps.includes(stepNumber)) {
            throw new Error(`Step ${stepNumber} out of bounds`)
        }
        if (this.approval_dates === null || typeof this.approval_dates === 'undefined') {
            this.approval_dates = {};
        }

        return Boolean(this.approval_dates[`step ${stepNumber}`])
    }

    approvalDateForStep(stepNumber) {
        if (!this.steps.includes(stepNumber)) {
            throw new Error(`Step ${stepNumber} out of bounds`)
        }

        if (this.approval_dates === null || typeof this.approval_dates === 'undefined') {
            this.approval_dates = {};
        }

        const stepKey = `step ${stepNumber}`;

        return (Object.keys(this.approval_dates).includes(stepKey)) ?
            new Date(Date.parse(this.approval_dates[stepKey])) :
            null;
    }

    firstDocumentOfType(docTypeId) {
        const typeDocs = this.documents
            .filter(d => d.document_type_id == docTypeId)
            .sort((a, b) => {
                if (a.date_reviewed == b.date_reviewed) {
                    return (a.version > b.version) ? 1 : -1;
                }

                return (a.date_reviewed > b.date_reviewed) ? 1 : -1;
            });

        return typeDocs[0] || {}
    }

    finalDocumentOfType(docTypeId) {
        const typeDocs = this.documents
            .filter(d => d.document_type_id == docTypeId)
            .sort((a, b) => {
                if (a.date_reviewed == b.date_reviewed) {
                    return (a.version > b.version) ? 1 : -1;
                }

                return (a.date_reviewed > b.date_reviewed) ? 1 : -1;
            });

        return typeDocs[(typeDocs.length - 1)] || {}
    }
}

export default Application