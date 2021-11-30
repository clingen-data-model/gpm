import Entity from './entity'

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
        draft_manuscripts: null,
        recuration_process_review: null,
        biocurator_training: null,
        biocurator_mailing_list: null,
        gci_training_date: null,
        gcep_attestation_date: null,
        type: {},
        review_protocol: {},
    };

    get nhgriSigned () {
        return Boolean(this.attributes['nhgri_attestation_date']);
    }
    set nhgriSigned (value) {
        this.attributes['nhgri_attestation_date'] = value ? new Date() : null;
    }

}

export default ExpertPanel;