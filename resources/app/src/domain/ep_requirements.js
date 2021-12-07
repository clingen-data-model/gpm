class Requirement {
    constructor (label, evaluator) {
        this.label = label;
        this.evaluator = evaluator;
    }

    isMet(group) {
        return this.evaluator(group);
    }
}

export const longName = new Requirement('Long Base Name', group => Boolean(group.expert_panel.long_base_name));
export const shortName = new Requirement('Short Base Name', group => Boolean(group.expert_panel.short_base_name));

export const chairs = new Requirement(
    '1+ Co-chairs', 
    group => group.chairs.length > 0
);
export const coordinators = new Requirement(
    '1+ Coordinator', 
    group => group.coordinators.length > 0
);

//TODO: why no workee
export const coisComplete = new Requirement(
    'All members completed COI', 
    group => group.members.filter(member => member.needsCoi).length == 0
);
export const diversityOfExpertise = new Requirement(
    'Diveristy of Expertise (need def)', 
    group => (false)
);
export const institutions = new Requirement(
    '3+ institutions represented', 
    group => new Set(group.members.map(m => m.person.institution_id)).size > 2
);
export const expertiseDescription = new Requirement(
    'Description of expertise',
    group => Boolean(group.expert_panel.membership_description)
);

export const genes = new Requirement('1+ genes in scope', group => group.expert_panel.genes.length > 0);
export const scopeDescription = new Requirement('Description of scope', group => Boolean(group.expert_panel.scope_description));

export const curationProcess = new Requirement(
    'Plans for sustained curation', 
    group => Boolean(group.expert_panel.curation_review_process_id)
);
export const meetingFrequency = new Requirement('Meeting frequency', group => Boolean(group.expert_panel.meeting_frequency));

export const nhgri = new Requirement('NHGRI Attestation', group => Boolean(group.expert_panel.nhgri_attestation_date));

export const gcepAttestation = new Requirement('GCEP processes Attestation', group => Boolean(group.expert_panel.gcep_attestation_date));

export const reanalysisAttestation = new Requirement('Reanalysis & discrepany resolution', group => Boolean(group.expert_panel.reanalysis_attestation_date));

// export const exampleSummaries = new Requirement('5+ Example Evidence Summaries', group => group.expert_panel.evidence_summaries.length > 4);
export const exampleSummaries = new Requirement('5+ Example Evidence Summaries', group => true);

class EpRequirements {
    static requirements = {};
    meetsRequirements (group, sections = null) {
        const requirements = this.getRequirementsFor(sections);

        return requirements.every(req => req.isMet(group));
    }

    checkRequirements (group, sections = null) {
        const requirements = this.getRequirementsFor(sections);

        return requirements.map(req => {
            return {
                label: req.label,
                isMet: req.isMet(group)
            }
        });
    }

    metRequirements (group, sections = null) {
        const requirements = this.getRequirementsFor(sections);
        
        return requirements.filter(req => req.isMet(group));
    }

    unmetRequirements (group, sections = null) {
        const requirements = this.getRequirementsFor(sections);
        
        return requirements.filter(req => !req.isMet(group));
    }

    getRequirementsFor(sections = null) {
        if (sections === null) {
            return Object.values(this.constructor.requirements).flat();
        }

        if (Array.isArray(sections)) {
            return sections.map(section => this.constructor.requirements[section])
                .flat();
        }

        if (typeof sections == 'string') {
            return this.constructor.requirements[sections]
        }

    }
}

export class GcepRequirements extends EpRequirements {
    static requirements = {
        basicInfo: [
            longName,
            shortName
        ],
        membership: [
            chairs,
            coordinators,
            coisComplete,
            diversityOfExpertise,
            institutions,
            // expertiseDescription,
        ],
        scope: [
            genes,
            scopeDescription
        ],
        attestations: [
            gcepAttestation,
            curationProcess,
            nhgri
        ]
    }
}

export class VcepRequirements extends EpRequirements {
    static requirements = {
        basicInfo: [
            longName,
            shortName
        ],
        membership: [
            chairs,
            coordinators,
            coisComplete,
            diversityOfExpertise,
            institutions,
            expertiseDescription,
        ],
        scope: [
            genes,
            scopeDescription
        ],
        curationReviewProcess: [
            meetingFrequency,
            curationProcess,
        ],
        evidenceSummaries: [
            exampleSummaries
        ],
        reanalysis: [
            reanalysisAttestation
        ],
        nhgri: [
            nhgri
        ]
    }

}