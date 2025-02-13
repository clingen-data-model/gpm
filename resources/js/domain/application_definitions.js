import {requirements} from '@/domain'

export class RequirementEvaluator {
    get hasRequirements () {
        return this.requirements.length > 0;
    }

    evaluateRequirements (group) {
        return this.requirements.map(req => {
            return {
                label: req.label,
                isMet: req.isMet(group)
            }
        });
    }

    meetsRequirements (group) {
        return this.requirements.every(req => req.isMet(group));
    }

    metRequirements (group) {
        return this.requirements.filter(req => req.isMet(group));
    }

    unmetRequirements (group) {
        return this.requirements.filter(req => !req.isMet(group));
    }
}
export class ApplicationSection extends RequirementEvaluator {
    constructor (name, title, requirements, components) {
        super();
        this.name = name;
        this.title = title;
        this.requirements = requirements;
        this.components = components;
    }
}
export class ApplicationStep extends RequirementEvaluator{
    constructor (name, sections, title, completeFunction, disabledFunction) {
        super();
        this.name = name;
        this.sections = sections;
        this.title = title;
        this.completeFunction = completeFunction;
        this.disabledFunction = disabledFunction;

        this.sections.forEach(sctn => {
            Object.defineProperty(
                this,
                sctn.name,
                {
                    get: () => {
                        return this.sections.find((sct => sct.name == sctn.name));
                    }
                }
            );
        });
    }

    get requirements () {
        return this.sections
                    .map(sct => sct.requirements)
                    .flat();
    }

    isComplete (group) {
        if (!this.completeFunction) {
            return false;
        }

        return this.completeFunction(group);
    }

    isDisabled (group) {
        if (!this.disabledFunction) {
            return false;
        }
        return this.disabledFunction(group);
    }
}
export class ApplicationDefinition extends RequirementEvaluator{
    constructor (steps) {
        super();
        this.steps = steps;
        this.steps.forEach(step => {
            Object.defineProperty(
                this,
                step.name,
                {
                    get: () => {
                        return this.steps.find(st => st.name == step.name)
                    }
                }
            );
        });
    }

    get sections () {
        return this.steps.map(step => step.sections).flat();
    }

    get requirements () {
        return this.sections.map(sct => sct.requirements).flat();
    }

    getStep(stepIdentifier) {
        if (isNaN(stepIdentifier)) {
            const step = this.steps.find(step => step.name == stepIdentifier);
            if (!step) {
                throw Error(`Step with name ${stepIdentifier} not found.`);
            }
            return step;
        }
        return this.steps[stepIdentifier-1];
    }

    getSection(sectionName) {
        return this.sections.find(sct => sct.name == sectionName);
    }

    getCurrentStep(group) {
        return this.getStep(group.expert_panel.current_step);
    }
}

export const applicationDefinitionFactory = (config) => {
    const steps = Object.keys(config.steps).map(stepKey => {
        const step = config.steps[stepKey];
        const sections  = Object.keys(step.sections).map(sectKey => {
            const section = step.sections[sectKey];
            return new ApplicationSection(sectKey, section.name, section.requirements, section.components)
        });
        return new ApplicationStep(stepKey, sections, step.name, step.completed, step.disabled);
    })
    return new ApplicationDefinition(steps);
}

export const VcepApplication = applicationDefinitionFactory({
    steps: {
        'definition': {
            name: 'Group Definition',
            sections: {
                basicInfo: {
                    name: 'Basic Information',
                    requirements: [
                        requirements.longName,
                        requirements.shortName
                    ],
                    // components: [
                    //     GroupForm,
                    // ]
                },
                membership: {
                    name: 'Membership',
                    requirements: [
                        requirements.chairs,
                        requirements.coordinators,
                        requirements.coisComplete,
                        requirements.institutions,
                        requirements.expertiseDescription,
                        requirements.memberExpertise
                    ],
                    // components: [
                        // MemberList,
                        // MembershipDescriptionForm
                    // ]
                },
                scope: {
                    name: 'Scope of Work',
                    requirements: [
                        requirements.genes,
                        requirements.scopeDescription
                    ],
                    // components: [
                        // ScopeDescriptionForm,
                        // VcepGeneList
                    // ]
                },
                reanalysis: {
                    name: 'Reanalysis & Discrepency Resolution',
                    requirements: [
                        requirements.reanalysisAttestation
                    ],
                    // components: [
                        // AttestationReanalysis,
                    // ]
                },
                nhgri: {
                    name: 'NHGRI Data Availability',
                    requirements: [
                        requirements.nhgri
                    ],
                    // components: [
                        // AttestationNhgri
                    // ]
                }

            },
            completed: group => group.expert_panel.defIsApproved,
            disabled: () => false,
        },
        'draft-specifications': {
            name: 'Draft Specifications',
            sections: {
                draftSpecs: {
                    name: 'Draft Specfications',
                    title: null,
                    requirements: [
                        requirements.draftApprovedSpecifications
                    ],
                }
                // components: [
                    // CspecSummary
                // ]
            },
            completed: group => {
                return group.expert_panel.draftSpecificationsIsApproved
            },
            disabled: group => !group.expert_panel.defIsApproved,
        },
        'pilot-specifications': {
            name: 'Specifications Pilot',
            sections: {
                pilotSpecs: {
                    name: 'Pilot Specifications',
                    requirements: [
                        requirements.pilotedApprovedSpecifications
                    ],
                }
                // components: [
                    // CspecSummary
                // ]
            },
            completed: group => group.expert_panel.pilotSpecificationsIsApproved,
            disabled: group => !group.expert_panel.draftSpecificationsIsApproved,
        },
        'sustained-curation': {
            name: 'Sustained Curation',
            sections: {
                curationReviewProcess: {
                    name: 'Curation and Review Process',
                    requirements: [
                        requirements.meetingFrequency,
                        requirements.curationProcess
                    ],
                    // components: [
                        // VcepOngoingPlansForm
                    // ]
                },
                evidenceSummaries: {
                    name: 'Example Evidence Summaries',
                    requirements: [
                        requirements.exampleSummaries
                    ],
                    // components: [
                        // EvidenceSummaryList
                    // ]
                },
                designations: {
                    name: 'Member Designation',
                    requirements: [
                        requirements.minimumBiocurators,
                        requirements.biocuratorTrainers,
                        requirements.coreApprovalMembers
                    ],
                    // components: [
                        // MemberDesignationForm
                    // ]
                },
            },
            completed: group => group.expert_panel.sustainedCurationIsApproved,
            disabled: group => !group.expert_panel.pilotSpecificationsIsApproved,
        }
    }
});

export const GcepApplication = applicationDefinitionFactory({
    steps: {
        definition: {
            title: 'Group Definition',
            sections: {
                basicInfo: {
                    name: 'Basic Information',
                    requirements: [
                        requirements.longName,
                        requirements.shortName
                    ],
                    // components: [
                        // GroupForm,
                    // ]
                },
                membership: {
                    name: 'Membership',
                    requirements: [
                        requirements.chairs,
                        requirements.coisComplete,
                        requirements.coordinators,
                        requirements.institutions,
                        requirements.memberExpertise
                    ],
                    // components: [
                        // MemberList,
                    // ]
                },
                scope: {
                    name: 'Scope of Work',
                    requirements: [
                        requirements.genes,
                        requirements.scopeDescription
                    ],
                    // components: [
                        // ScopeDescriptionForm,
                        // GcepGeneList
                    // ]
                },
                attestations: {
                    name: 'Attestations',
                    requirements: [
                        requirements.gcepAttestation
                    ],
                    // components: [
                    //     AttestationGcep,
                    // ]
                },
                curationReviewProcess: {
                    name: 'Curation and Review Process',
                    requirements: [
                        requirements.curationProcess
                    ],
                    // components: [
                    //     GcepOngoingPlansForm
                    // ]
                },
                nhgri: {
                    name: 'NHGRI Data Availability',
                    requirements: [
                        requirements.nhgri
                    ],
                    // components: [
                    //     AttestationNhgri
                    // ]
                }

            }
        },
    }
});
