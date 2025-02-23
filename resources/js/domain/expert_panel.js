import configs from '@/configs'
import Entity from './entity'

const stepNumberToName = {1: 'Definition', 2: 'Draft', 3: 'Pilot', 4: 'Sustained Curation'}
const {submissions} = configs;
class ExpertPanel extends Entity
{
    static dates = [
        'date_completed',
        'date_initiated',
        'step_1_received_date',
        'step_1_approval_date',
        'step_2_approval_date',
        'step_3_approval_date',
        'step_4_received_date',
        'step_4_approval_date',
        'created_at',
        'updated_at',
        'deleted_at',
        'gcep_attestation_date',
        'gci_training_date',
        'nhgri_attestation_date',
        'reanalysis_attestation_date'
    ];

    static defaults = {
        id: null,
        uuid: null,
        group_id: null,
        expert_panel_type_id: null,
        long_base_name: null,
        short_base_name: null,
        affiliation_id: null,
        current_step: 1,
        date_initiated: null,
        step_1_received_date: null,
        step_1_approval_date: null,
        step_2_approval_date: null,
        step_3_approval_date: null,
        step_4_received_date: null,
        step_4_approval_date: null,
        date_completed: null,
        coi_code: null,
        hypothesis_group: null,
        membership_description: null,
        scope_description: null,
        nhgri_attestation_date: null,
        curation_review_protocol_id: null,
        curation_review_protocol_other: null,
        curation_review_process_notes: null,
        meeting_frequency: null,
        reanalysis_conflicting: null,
        reanalysis_review_lp: null,
        reanalysis_review_lb: null,
        reanalysis_other: null,
        reanalysis_attestation_date: null,
        utilize_gt: null,
        utilize_gci: null,
        curations_publicly_available: null,
        pub_policy_reviewed: null,
        biocurator_training: null,
        biocurator_mailing_list: null,
        draft_manuscripts: null,
        gci_training_date: null,
        gcep_attestation_date: null,
        recuration_process_review: null,
        review_protocol: {},
        type: {},
        evidence_summaries: []
    };

    constructor (attributes = {}) {
        const specifications = attributes.specifications ? [...attributes.specifications] : [];
        delete(attributes.specifications);

        const genes = attributes.genes ? [...attributes.genes] : [];
        delete(attributes.genes);

        const submissions = attributes.submissions ? [...attributes.submissions] : [];
        delete(attributes.submissions);

        const documents = attributes.documents ? [...attributes.documents] : [];
        delete(attributes.documents);

        const nextActions = attributes.next_actions ? [...attributes.next_actions] : [];
        delete(attributes.next_actions);

        super(attributes);

        this.specfications = specifications;
        this.genes = genes;
        this.submissions = submissions;
        this.documents = documents;
        this.nextActions = nextActions;
    }

    get next_actions () {
        return this.nextActions
    }

    get nhgriSigned () {
        return Boolean(this.attributes['nhgri_attestation_date']);
    }
    set nhgriSigned (value) {
        this.attributes['nhgri_attestation_date'] = value ? new Date() : null;
    }

    get approvedDraftSpecifications () {
        return [];
    }

    get approvedPilotedSpecifications () {
        return [];
    }

    get defIsApproved () {
        return this.step_1_approval_date !== null
    }
    get definitionIsApproved () {
        return this.step_1_approval_date !== null
    }

    get draftSpecificationsIsApproved () {
        return this.step_2_approval_date !== null
    }
    get draftSpecApproved () {
        return this.step_2_approval_date !== null
    }


    get pilotSpecificationsIsApproved () {
        return this.step_3_approval_date !== null
    }
    get pilotSpecApproved () {
        return this.step_3_approval_date !== null
    }

    get sustainedCurationIsApproved () {
        return this.step_4_approval_date !== null
    }

    get pendingSubmission () {
        return this.submissions.find(submission => submission.is_pending) || {};
    }

    get hasPendingSubmission () {
        return this.submissions
                .filter(submission => submission.is_pending)
                .length > 0
    }

    get hasPendingSubmissionForCurrentStep () {
        return this.hasPendingSubmission && this.pendingSubmission.type.name == this.currentStepName;
    }

    get hasCompletedSubmission () {
        return this.submissions
                .filter(sub => {
                    return sub.submission_status_id == submissions.statuses.approved.id
                        && this.pendingSubmission.type.name == this.currentStepName
                })
    }

    get isCompleted() {
        if (this.attributes.date_completed) {
            return true;
        }

        return false;
    }

    get steps() {
        if (this.expert_panel_type_id == 1) {
            return [1];
        }
        if (this.expert_panel_type_id == 2 || this.expert_panel_type_id === null) {
            return [1, 2, 3, 4];
        }

        throw new Error('Unknown expert_panel_type_id found when determining applicaiton steps.');
    }

    get currentStepName() {
        switch (this.current_step) {
            case 1:
                return 'Definition';
            case 2:
                return 'Draft Specifications';
            case 3:
                return 'Pilot Specifications';
            case 4:
                return 'Sustained Curation';
            default:
                throw new Error('Unknown step '.this.current_step);
        }
    }

    get currentStepAbbr () {
        switch (this.current_step) {
            case 1:
                return 'Definition';
            case 2:
                return 'Draft';
            case 3:
                return 'Pilot';
            case 4:
                return 'Sustained';
            default:
                throw new Error('Unknown step '.this.current_step);
        }
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

    get isApplying () {
        return this.date_completed === null;
    }

    submissionsForStep (step) {
        const stepName = (typeof step == 'number') ? stepNumberToName[step] : step;
        return this.submissions.filter(s => s.type.name = stepName)
    }

    hasPendingSubmissionForStep(stepName) {
        return this.hasPendingSubmission && this.pendingSubmission.type.name == stepName;
    }

    hasPendingSubmissionForStepNumber(step) {
        return this.hasPendingSubmission && this.pendingSubmission.type.name == stepNumberToName[step];
    }

    latestPendingSubmissionForStep(step) {
        const stepName = (typeof step == 'number') ? stepNumberToName[step] : step;
        const stepSubmissions = this.submissions.filter(s => s.type.name = stepName);

        if (stepSubmissions.length == 0) {
            return null;
        }

        return stepSubmissions[stepSubmissions.length - 1];
    }

    stepHasBeenSubmitted(step) {
        return this.submissions.some(s => s.type.name == stepNumberToName[step])
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
            throw new Error(`Step ${stepNumber} out of bounds`)
        }

        return Boolean(this[`step_${stepNumber}_approval_date`]);
    }

    approvalDateForStep(stepNumber) {
        if (!this.steps.includes(stepNumber)) {
            throw new Error(`Step ${stepNumber} out of bounds`)
        }

        const stepKey = `step_${stepNumber}_approval_date`;

        return (this[stepKey]) ?
            new Date(Date.parse(this[stepKey])) :
            null;
    }

    updateApprovalDate(newDate, stepNumber) {
        if (!this.steps.includes(stepNumber)) {
            throw new Error(`Step ${stepNumber} out of bounds`)
        }

        const stepKey = `step_${stepNumber}_approval_date`;

        this[stepKey] = newDate;
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

export default ExpertPanel;
