import Entity from './entity'
class Application extends Entity{
    static dates = [
        'date_completed',
        'date_initiated',
        'created_at', 
        'updated_at',
        'deleted_at'
    ];

    static defaults = {
        working_name: null,
        ep_type_id: null,
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
        return this.ep_type_id == 1;
    }

    get isVcep() {
        return this.ep_type_id == 2;
    }

    get steps() {
        if (this.ep_type_id == 1) {
            return [1];
        }
        if (this.ep_type_id == 2) {
            return [1,2,3,4];
        }

        throw new Error('Unknown ep_type_id found when determining applicaiton steps.');
    }

    get hasContacts() {
        return (this.contacts.length > 0);
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

        return (Object.keys(this.approval_dates).includes(stepKey)) 
                    ? new Date(Date.parse(this.approval_dates[stepKey])) 
                    : null;
    }

    firstDocumentOfType(docTypeId) {
        const typeDocs = this.documents
                            .filter(d =>  d.document_type_id == docTypeId)
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
                            .filter(d =>  d.document_type_id == docTypeId)
                            .sort((a, b) => {
                                if (a.date_reviewed == b.date_reviewed) {
                                    return (a.version > b.version) ? 1 : -1;
                                }

                                return (a.date_reviewed > b.date_reviewed) ? 1 : -1;
                            });

        return typeDocs[(typeDocs.length-1)] || {}
    }
}

export default Application