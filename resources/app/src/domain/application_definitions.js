import AttestationGcep from '@/components/expert_panels/AttestationGcep'
import AttestationNhgri from '@/components/expert_panels/AttestationNhgri'
import AttestationReanalysis from '@/components/expert_panels/AttestationReanalysis'
import CspecSummary from '@/components/expert_panels/CspecSummary'
import EvidenceSummaryList from '@/components/expert_panels/EvidenceSummaryList'
import GroupForm from '@/components/groups/GroupForm';
import MemberDesignationForm from '@/components/expert_panels/MemberDesignationForm';
import MemberList from '@/components/groups/MemberList';
import MembershipDescriptionForm from '@/components/expert_panels/MembershipDescriptionForm';
import ScopeDescriptionForm from '@/components/expert_panels/ScopeDescriptionForm';
import VcepGeneList from '@/components/expert_panels/VcepGeneList';
import VcepOngoingPlansForm from '@/components/expert_panels/VcepOngoingPlansForm';

import {
    longName,
    shortName,
    chairs,
    coordinators,
    coisComplete,
    diversityOfExperience,
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
    pilotedApprovedSpecifications,
    minimumBiocurators,
    biocuratorTrainers,
    coreApprovalMembers
} from '@/domain/ep_requirements'

export const vcepApplication = {
    steps: {
        definition: {
            name: 'Group Definition',
            sections: {
                basicInfo: {
                    name: 'Basic Information',
                    requirements: [
                        longName,
                        shortName
                    ],
                    components: [
                        GroupForm,
                    ]
                },
                membership: {
                    name: 'Membership',
                    requirements: [
                        chairs,
                        coordinators,
                        coisComplete,
                        diversityOfExpertise,
                        institutions,
                        expertiseDescription,            
                    ],
                    components: [
                        MemberList,
                        MembershipDescriptionForm        
                    ]
                },
                scope: {
                    name: 'Scope of Work',
                    requirements: [
                        genes,
                        scopeDescription
                    ],
                    components: [
                        ScopeDescriptionForm,
                        VcepGeneList
                    ]
                },
                reanalysis: {
                    name: 'Reanalysis & Discrepency Resolution',
                    requirements: [
                        reanalysisAttestation
                    ],
                    components: [
                        AttestationReanalysis,
                    ]
                },
                nhgri: {
                    label: 'NHGRI Data Availability',
                    requirements: [
                        nhgri
                    ],
                    components: [
                        AttestationNhgri
                    ]
                }
        
            },
            completed: group => group.expert_panel.defitionIsApproved,
        },
        'draft-specifications': {
            name: 'Draft Specifications',
            sections: {
                requirements: [
                    pilotedDraftSpecifications
                ],
                components: [
                    CspecSummary
                ]
            },
            completed: group => group.expert_panel.draftSpecificationsIsApproved,
            disabled: (group) => !group.expert_panel.definitionIsApproved,
        },
        'pilot-specifications': {
            name: 'Specifications Pilot',
            sections: {
                requirements: [
                    pilotedApprovedSpecifications
                ],
                components: [
                    CspecSummary
                ]
            },
            completed: group => group.expert_panel.pilotSpecificationsIsApproved,
            disabled: (group) => !group.expert_panel.draftSpecificationsIsApproved,
        },
        'sustained-curation': {
            name: 'Sustained Curation',
            sections: {
                curationReviewProcess: {
                    name: 'Curation and Review Process',
                    requirements: [
                        meetingFrequency,
                        curationProcess
                    ],
                    components: [
                        VcepOngoingPlansForm
                    ]
                },
                evidenceSummaries: {
                    name: 'Example Evidence Summaries',
                    requirements: [
                        exampleSummaries
                    ],
                    components: [
                        EvidenceSummaryList
                    ]
                },
                designations: {
                    name: 'Member Designation',
                    requirements: [
                        minimumBiocurators,
                        biocuratorTrainers,
                        coreApprovalMembers
                    ],
                    components: [
                        MemberDesignationForm
                    ]
                },
    
            },
            completed: group => group.expert_panel.sustainedCurationIsApproved,
            disabled: (group) => !group.expert_panel.pilotSpecificationsIsApproved,
        }
        
    }
}

export const gcepApplication = {
    steps: {
        definition: {
            sections: {
                basicInfo: {
                    name: 'Basic Information',
                    requirements: [
                        longName,
                        shortName
                    ],
                    components: [
                        GroupForm,
                    ]
                },
                membership: {
                    name: 'Membership',
                    requirements: [
                        chairs,
                        coisComplete,
                        coordinators,
                        institutions,
                        diversityOfExpertise,
                    ],
                    components: [
                        MemberList,
                    ]
                },
                scope: {
                    name: 'Scope of Work',
                    requirements: [
                        genes,
                        scopeDescription
                    ],
                    components: [
                        ScopeDescriptionForm,
                        GcepGeneList
                    ]
                },
                attestations: {
                    name: 'Attestations',
                    requirements: [
                        gcepAttestation
                    ],
                    components: [
                        AttestationGcep,
                    ]
                },
                curationReviewProcess: {
                    name: 'Curation and Review Process',
                    requirements: [
                        curationProcess
                    ],
                    components: [
                        GcepOngoingPlansForm
                    ]
                },
                nhgri: {
                    name: 'NHGRI Data Availability',
                    requirements: [
                        nhgri
                    ],
                    components: [
                        AttestationNhgri
                    ]
                }
        
            }
        },
    }
}