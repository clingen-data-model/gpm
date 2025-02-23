import configs from '@/configs'
import Entity from './entity'

class Application extends Entity {
    static dates = [
        "date_completed",
        "date_initiated",
        "created_at",
        "updated_at",
        "deleted_at",
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
        contacts: [],
    };

    get isCompleted() {
        if (this.attributes.date_completed) {
            return true;
        }

        return false;
    }

    get steps() {
        if (this.is_gcep == 1) {
            return [1];
        }
        if (this.is_vcep_or_scvcep) {
            return [1, 2, 3, 4];
        }

        // eslint-disable-next-line no-console
        console.log(this);
        throw new Error(
            `Unknown expert_panel_type_id found when determining application steps. Value: ${this}`
        );
    }

    get pendingNextActions() {
        if (!this.next_actions) {
            return [];
        }
        return this.next_actions.filter((na) => na.date_completed === null);
    }

    get pendingActionsByAssignee() {
        const naAssignees = Object.values(configs.nextActions.assignees).map(
            (na) => na.id
        );
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
        return groups;
    }

    get hasContacts() {
        return this.contacts.length > 0;
    }

    isWaitingOnCdwgOc() {
        return this.pendingActionsAssigneeCounts.cdwg_oc > 0;
    }

    isWaitingOnExpertPanel() {
        return this.pendingActionsAssigneeCounts.expert_panel > 0;
    }

    stepIsApproved(stepNumber) {
        if (!stepNumber) {
            return false;
        }
        if (!this.steps.includes(stepNumber)) {
            throw new Error(`Step ${stepNumber} out of bounds`);
        }

        return Boolean(this[`step_${stepNumber}_approval_date`]);
    }

    approvalDateForStep(stepNumber) {
        if (!this.steps.includes(stepNumber)) {
            throw new Error(`Step ${stepNumber} out of bounds`);
        }

        const stepKey = `step_${stepNumber}_approval_date`;

        return this[stepKey] ? new Date(Date.parse(this[stepKey])) : null;
    }

    firstDocumentOfType(docTypeId) {
        const typeDocs = this.documents
            .filter((d) => d.document_type_id == docTypeId)
            .sort((a, b) => {
                if (a.date_reviewed == b.date_reviewed) {
                    return a.version > b.version ? 1 : -1;
                }

                return a.date_reviewed > b.date_reviewed ? 1 : -1;
            });

        return typeDocs[0] || {};
    }

    finalDocumentOfType(docTypeId) {
        const typeDocs = this.documents
            .filter((d) => d.document_type_id == docTypeId)
            .sort((a, b) => {
                if (a.date_reviewed == b.date_reviewed) {
                    return a.version > b.version ? 1 : -1;
                }

                return a.date_reviewed > b.date_reviewed ? 1 : -1;
            });

        return typeDocs[typeDocs.length - 1] || {};
    }
}

export default Application