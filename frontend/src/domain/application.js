class Application {

    constructor(attributes = {}) {
        this.dates = [
            'date_completed',
            'date_initiated',
            'created_at', 
            'updated_at',
            'deleted_at'
        ];

        const defaults = {
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

        this.attributes = {...defaults, ...attributes}

        for (let attr in this.attributes) {
            this.defineAttributeGettersAndSetters(attr)
        }
    }

    defineAttributeGettersAndSetters(attr) 
    {
        let setter = (value) => {
            this.attributes[attr] = value
        }

        let getter = () => {
            return this.attributes[attr]
        }

        if (this.dates.includes(attr)) {
            getter = () => (this.attributes[attr]) ? new Date(Date.parse(this.attributes[attr])) : null;
            setter = (value) => {
                this.attributes[attr] = (value) ? new Date(Date.parse(value)) : null;
            }
        }

        Object.defineProperty(
            this, 
            attr, 
            { 
                get: getter, 
                set: setter
            }
        );
    }

    setAttribute(attr, value=null) {
        if (typeof Object.getOwnPropertyDescriptor(this, attr) == 'undefined') {
            this.defineAttributeGettersAndSetters(attr);
        }

        this[attr] = value;
    }

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

    stepIsApproved(stepNumber) {
        if (!this.steps.includes(stepNumber)) {
            throw new Error(`Step ${stepNumber} out of bounds`)
        }
        if (this.approval_dates === null) {
            this.approval_dates = {};
        }

        return Boolean(this.approval_dates[`step ${stepNumber}`])
    }

    approvalDateForStep(stepNumber) {
        if (!this.steps.includes(stepNumber)) {
            throw new Error(`Step ${stepNumber} out of bounds`)
        }

        const stepKey = `step ${stepNumber}`;

        return (Object.keys(this.approval_dates).includes(stepKey)) 
                    ? new Date(Date.parse(this.approval_dates[stepKey])) 
                    : null;
    }

    firstDocumentOfType(docTypeId) {
        const typeDocs = this.documents
                            .filter(d =>  d.document_category_id == docTypeId)
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
                            .filter(d =>  d.document_category_id == docTypeId)
                            .sort((a, b) => {
                                if (a.date_reviewed == b.date_reviewed) {
                                    return (a.version > b.version) ? 1 : -1;
                                }

                                return (a.date_reviewed > b.date_reviewed) ? 1 : -1;
                            });

        return typeDocs[(typeDocs.length-1)] || {}
    }

    clone() {
        return new Application(this.attributes);
    }
}

export default Application