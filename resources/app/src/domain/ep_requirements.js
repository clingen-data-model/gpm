export class Requirement {
    constructor (label, evaluator) {
        this.label = label;
        this.evaluator = evaluator;
    }

    isMet(group) {
        return this.evaluator(group);
    }
}

const isEmpty = (val) => {
    switch (typeof val) {
        case 'undefined':
            return true;
        case 'object':
            if (val === null) return true;
            return (Object.keys(val).length === 0);
        case 'array':
            return (val.length === 0);
        case 'string':
            return (val.trim() === '');
        default:
            return Boolean(val);
    }
}

export const longName = new Requirement('Long Base Name', group => !isEmpty(group.expert_panel.long_base_name));
export const shortName = new Requirement('Short Base Name', group => !isEmpty(group.expert_panel.short_base_name));

export const chairs = new Requirement('1+ Co-chairs', group => !isEmpty(group.chairs));
export const coordinators = new Requirement('1+ Coordinator', group => !isEmpty(group.coordinators));

export const coisComplete = new Requirement('All members completed COI', group => isEmpty(group.members.filter(member => member.needsCoi)));
// export const diversityOfExpertise = new Requirement('Diveristy of Expertise (need def)', group => (true));
export const institutions = new Requirement(
    '3+ institutions represented', 
    group => {
        const institutionIds = group.members
                                .filter(m => Boolean(m.person.institution))
                                .map(m => m.person.institution.id);        
        return new Set(institutionIds).size > 2
    }
);
export const expertiseDescription = new Requirement(
    'Description of expertise',
    group => !isEmpty(group.expert_panel.membership_description)
);

export const genes = new Requirement('1+ genes in scope', group => group.expert_panel.genes.length > 0);
export const scopeDescription = new Requirement('Description of scope', group => !isEmpty(group.expert_panel.scope_description));

export const curationProcess = new Requirement(
    'Plans for sustained curation', 
    group => Boolean(group.expert_panel.curation_review_protocol_id)
);
export const meetingFrequency = new Requirement('Meeting frequency', group => !isEmpty(group.expert_panel.meeting_frequency));

export const nhgri = new Requirement('NHGRI Attestation', group => !isEmpty(group.expert_panel.nhgri_attestation_date));

export const gcepAttestation = new Requirement('GCEP processes Attestation', group => !isEmpty(group.expert_panel.gcep_attestation_date));

export const reanalysisAttestation = new Requirement('Reanalysis & discrepany resolution', group => !isEmpty(group.expert_panel.reanalysis_attestation_date));

export const exampleSummaries = new Requirement(
                                    '5+ Example Evidence Summaries', 
                                    group => group.expert_panel.evidence_summaries.length > 4
                                );

export const draftApprovedSpecifications = new Requirement(
    '1+ Approved piloted, specifications document.',
    group => group.expert_panel.approvedDraftSpecifications.length > 0
)

export const pilotedApprovedSpecifications = new Requirement(
    '1+ Approved piloted, specifications document.',
    group => group.expert_panel.approvedPilotedSpecifications.length > 0
)

export const minimumBiocurators = new Requirement(
    '3+ trained biocurators.',
    group => {
        return group.biocurators
                .filter(biocurator => {
                    return biocurator.training_level_1 
                        && biocurator.training_level_2
                }).length > 2
    }
);

export const biocuratorTrainers = new Requirement(
    '1+ biocurator trainers.',
    group => {
        return group.biocuratorTrainers.length > 0
    }
);

export const coreApprovalMembers = new Requirement(
    '1+ core approval members',
    group => {
        return group.coreApprovalMembers.length > 0
    }
)

export const memberExpertise = new Requirement (
    'Expertise information must be provided for each group member',
    group => group.members
                .filter(m => {
                    return m.expertise === null 
                        || typeof m.expertise === 'undefined' 
                        || m.expertise == ''
                })
                .length == 0
)

export default {
    longName,
    shortName,
    chairs,
    coordinators,
    coisComplete,
    institutions,
    expertiseDescription,
    genes,
    scopeDescription,
    curationProcess,
    meetingFrequency,
    nhgri,
    gcepAttestation,
    reanalysisAttestation,
    exampleSummaries,
    draftApprovedSpecifications,
    pilotedApprovedSpecifications,
    minimumBiocurators,
    biocuratorTrainers,
    coreApprovalMembers,
    memberExpertise,
};