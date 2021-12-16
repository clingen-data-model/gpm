import Entity from './entity'
import configs from '@/configs'

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
        long_base_name: {},
        short_base_name: {},
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

        super(attributes);

        this.specfications = specifications;
        this.genes = genes;
        this.submissions = submissions;
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
    // get definitionIsApproved () {
    //     return this.step_1_approval_date !== null
    // }

    get draftSpecificationsIsApproved () {
        return this.step_2_approval_date !== null
    }

    get pilotSpecificationsIsApproved () {
        return this.step_3_approval_date !== null
    }

    get sustainedCurationIsApproved () {
        return this.step_4_approval_date !== null
    }

    get pendingSubmission () {
        return this.submissions.find(submission => submission.submission_status_id == submissions.statuses.pending.id) || {};
    }

    get hasPendingSubmission () {
        return this.submissions
                .filter(submission => submission.submission_status_id == submissions.statuses.pending.id)
                .length > 0
    }

    get hasCompletedSubmission () {
        return this.submissions
                .filter(submission => submission.submission_status_id == submissions.statuses.approved.id)
    }

    hasPendingSubmissionForStep(stepName) {
        return this.hasPendingSubmission && this.pendingSubmission.type.name == stepName;
    }
}

export default ExpertPanel;