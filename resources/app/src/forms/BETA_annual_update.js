import VcepTotals from '@/components/annual_update/VcepTotals.vue'
import MemberDesignationForm from '@/components/expert_panels/MemberDesignationForm.vue'

const yesNoOptions = [{value: 'yes'}, {value: 'no'}];
const goalsForm = {
    type: 'section',
    name: 'goals',
    label: 'Goals for next year',
    contents: [
        {
            name: 'goals',
            type: 'large-text',
            label: 'Describe the Expert Panel\’s plans and goals for the next year, for example, finishing curations, working on manuscript, etc'
        },
        {
            name: 'cochair_commitment',
            label: 'Do the co-chairs plan to continue leading the EP for the next year?',
            type: 'radio-group',
            options: yesNoOptions
        },
        {
            name: 'cochair_commitment_details',
            label: 'Please Explain',
            type: 'large-text',
            show: modelValue => modelValue.data.cochair_commitment == 'no'
        }

    ]
};
const additionalFunding = {
    type: 'section',
    name: 'additional-funding',
    label: 'AdditionalFunding',
    contents: [
        {
            name: 'applied_for_funcding',
            label: "Have you applied for external funding for your EP?",
            type: 'radio-group',
            options: yesNoOptions
        },
        {
            name: 'funding',
            label: 'Funding Type',
            type: 'select',
            options: [
                {value: 'NIH U24'},
                {value: 'Professional society'},
                {value: 'Patient Advocacy Group'},
                {value: 'Pharma'},
                {value: 'Industry'},
                {value: 'Other'},
            ]
        },
        {
            name: 'funding_other_details',
            label: 'Please describe any thoughts, ideas, or plans for soliciting funding or personnel (in addition to any existing funding/support you may have).',
            type: 'large-text',
            show: modalValue => modelValue.data.funding == 'Other'
        }
    ]
};

const websiteAttestation = {
    name: 'website_attestation',
    label: 'Please review your ClinGen EP webpage, including Expert Panel Status, description, membership, COI and relevant documentation, including publications. See the <coordinator-resource-link /> for instructions on how to update web pages.',
    dataType: 'boolean',
    type: 'component',
    component: {
        component: websiteAttestation
    }
}

const gcep_update_form_2022 = [
    { name: 'gci-gt-use',
        type: 'section',
        label: 'Use of GCI and GeneTracker Systems',
        contents: [
            { name: 'gci-use',
                type: 'radio-group',
                label: 'Does your Expert Panel use the GCI for all gene curation activities?',
                options: yesNoOptions
            },
            { name: 'gci_use_details',
                type: 'large-text',
                label: 'Please explain',
                show: modalValue => modelValue.data.gci_use == 'no'
            },
            { name: 'gt_gene_list',
                label: "Our complete gene list (genes previously approved or currently under consideration) has been added to the GeneTracker?",
                options: yesNoOptions,
            },
            { name: 'gt_gene_list_detail',
                label: "Please Explain",
                type: 'large-text',
                show: modelValue => modelValue.gt_gene_list == 'no'
            },
            { name: 'gt_precuration_info',
                label: 'All applicable precuration information has been added to the GeneTracker.',
                type: 'radio-group',
                options: yesNoOptions
            },
            { name: 'gt_precuration_info_details',
                label: "Please Explain",
                type: 'large-text',
                show: modelValue => modelValue.gt_precuration_info == 'no'
            },
        ]
    },
    { name: 'gene-curation-totals',
        label: 'Summary of total numbers of genes curated',
        type: 'section',
        contents: [
            // test block:
            // <p>
            //     In reviewing the GCEPs progress over the last year ({{lastYear}}), please answer the following questions. Note, This information can be accessed from the GCI, GeneTracker, and in some instances (e.g. published curations) from the ClinGen website.
            //     This information can be downloaded from the <gene-tracker-link /> or the <gci-link />.
            //     <br>
            //     Please cross reference <website-link /> for published records.
            // </p>
            { name: 'published_count',
                label: 'Approved and published on the ClinGen Website.',
                type: 'number'
            },
            { name: 'approved_unpublished_count',
                label: 'Approved and pending publishing on the Clingen Website.',
                type: 'number'
            },
            { name: 'in_progress_count',
                label: 'In progress in the GCI.',
                type: 'number'
            }
        ]
    },
    {
        type: 'section',
        name: 'gcep-ongoing-plans',
        label: 'Changes to plans for ongoing curation',
        contents: [
            { name: 'curation_review_protocol_id',
                label: "Three examples of ClinGen-approved curation and review protocols are below (additional details may be requested from the CDWG Oversight Committee).  Check or describe the curation and review protocol that this Expert Panel will use.",
                type: 'radio-group',
                options: [
                    {
                        value: 1,
                        label: 'Single biocurator curation with comprehensive GCEP review (presentation of all data on calls with GCEP votes). Note: definitive genes may be expedited with brief summaries.'
                    },
                    {
                        value: 2,
                        label: 'Paired review (biocurator & domain expert) with expedited GCEP review. Expert works closely with a curator on the initial summation of the information for expedited GCEP review (brief summary on a call with GCEP voting and/or electronic voting by GCEP). Definitive genes can move directly from biocurator to expedited GCEP review.'
                    },
                    {
                        value: 3,
                        label: 'Dual biocurator review with expedited GCEP review for concordant genes and full review for discordant genes.'
                    },
                    {
                        value: 100,
                        label: 'Other'
                    }
                ]
            },
            { name: 'curation_review_protocol_other',
                label: 'Please explain',
                type: 'large-text',
                show: modelValue => modelValue.curation_review_protocol_id == 100
            },
            { name: 'ongoing_plans_updated',
                label: 'Does this current review method represent a change from previous years?',
                type: 'radio-group',
                options: yesNoOptions
            },
            { name: 'ongoing_plans_update_details',
                label: 'Please explain',
                type: 'large-text',
                show: modelValue => modelValue.data.ongoing_plans_updated == 'yes'
            }
        ]
    },
    {
        type: 'section',
        name: 'gcep-rerereview-form',
        label: 'Gene Re-curation/Re-review',
        contents: [
            // <p>Gene Curation Expert Panels are expected to review current clinical validity classifications for their approved genes based on the guidance provided in the <gcep-recuration-process-link />. Please indicate the following:</p>
            { name: 'recuration_begun',
                label: 'Have you begun recuration?',
                type: 'radio-group',
                options: yesNoOptions
            },
            { name: 'recuration_designees',
                label: 'The GCEP recuration designee(s) is designated to monitor for recuration updates, on a yearly basis, according to the <gcep-recuration-process-link />. Please list the name(s) of your GCEP recuration designee(s).',
                type: 'large-text',
                placeholder: 'First Last, Email'
            }
        ]
    },
    goalsForm,
    additionalFunding,
    websiteAttestation,
];

const vcep_update_2022 = [
    {
        type: 'section',
        name: 'vci-use',
        label: 'Use of Variant Curation Interface (VCI)',
        contents: [
            { name: 'vci_use',
                label: 'Please Indicate whether the Expert Panel uses the VCI for all variant curation activities, or if the Expert Panel intends to use the VCI once they begin curation. If not used for all activities, please describe.',
                type: 'radio-group',
                options: yesNoOptions
            },
            { name: 'vci_use_details',
                label: 'Please explain',
                type: 'large-text',
                show: modelValue => modelValue.data.vci_use == 'no'
            },
        ]
    },
    goalsForm,
    additionalFunding,
    websiteAttestation,
    {
        type: 'section',
        name: 'specification-progress',
        label: 'Progress on Rule Specification',
        contents: [
            { name: specification_progress_url,
                label: 'Link to approved specification in Cspec',
                type: 'text',
            },
            { name: 'specification_progress',
                label: "Have you made any changes or additions to your ACMG/AMP specifications for the gene(s) of interest, including evidence and rationale to support the rule specifications.",
                type: 'radio-group',
                options: [
                    {
                        label: 'Not applicable, VCEP specifications to the ACMG/AMP guidelines in progress',
                        value: 'not-applicable'
                    },
                    {
                        value: 'no-changes',
                        label: 'No, VCEP has made no changes to the ClinGen-approved VCEP specifications to the ACMG/AMP guidelines'
                    },
                    {
                        value: 'yes-pending-approval',
                        label: 'Yes, VCEP has proposed changes to the ClinGen-approved VCEP specifications to the ACMG/AMP guidelines, but the updates are not yet approved by the SVI VCEP Review Committee'
                    },
                    {
                        value: 'yes-approved',
                        label: 'Yes, VCEP has proposed changes to the ClinGen-approved VCEP specifications to the ACMG/AMP guidelines and the updates have been approved by the SVI VCEP Review Committee'
                    }
                ]
            },
            { name: 'specification_progress_details',
                label: 'Describe changes',
                type: 'large-text',
                show: modelValue => ['yes-pending-approval', 'yes-approved'].includes(modelValue.data.specification_progress)
            },
            { name: 'specifications_for_new_gene',
                label: 'Are you planning to start the rule specification process for a new gene in the coming year?',
                type: 'radio-group',
                options: yesNoOptions
            },
            { name: 'specifications_for_new_gene_details',
                label: "What are your plans?",
                type: 'large-text',
                show: modelValue => modelValue.data.specifications_for_new_gene == 'yes'
            }
        ]
    },
    {
        type: 'section',
        name: 'vcep-totals',
        label: 'Summary of total number of variants curated',
        contents: []
    },
    {
        type: 'section',
        name: 'variant-reanalysis',
        label: '',
        contents: [
            { name: 'variant_counts',
                label: '',
                type: 'component',
                component: {
                    component: VcepTotals
                }
            }
        ]
    },
    {
        type: 'section',
        name: 'vcep-ongoing-plans',
        label: '',
        contents: [
            { name: 'meeting_frequency',
                label: 'Meeting/call frequency',
                type: 'text',
                placeholder: 'Once per week'
            },
            { name: 'curation_review_protocol_id',
                label: 'VCEP Standardized Review Process',
                type: 'radio-group',
                options: [
                    {value: 1, label: 'Process #1: Biocurator review followed by VCEP discussion'},
                    {value: 2, label: 'Process #2: Paired biocurator/expert review followed by expedited VCEP approval'}
                ]
            },
            // For all variants approved by either of the processes described above, a summary of approved variants should be sent to ensure that any members absent from a call have an opportunity to review each variant. The summary should be emailed to the full VCEP after the call and should summarize decisions that were made and invite feedback within a week.
            { name: 'curation_review_process_notes',
                label: 'Curation and Review Process Notes',
                type: 'large-text',
            },
            { name: 'ongoing_plans_updated',
                label: 'Does this current review method represent a change from previous years?',
                type: 'radio-group',
                options: yesNoOptions
            },
            { name: 'ongoing_plans_update_details',
                label: 'Please explain',
                type: 'large-text',
                show: modelValue => modelValue.data.ongoing_plans_updated == 'yes'
            },
            { name: 'changes_to_call_frequency',
                label: 'Have there been any changes to your VCEP’s workflow or meeting/call frequency in the last year?',
                type: 'radio-group',
                options: yesNoOptions
            },
            { name: 'changes_to_call_frequency_details',
                label: 'Please explain',
                type: 'large-text',
                show: workingCopy => workingCopy.data.changes_to_call_frequency == 'yes'
            }
        ]
    },
    {
        type: 'section',
        name: 'member-designation',
        label: '',
        contents: [
            { name: 'member-designation',
                type: 'component',
                component: {
                    component: MemberDesignationForm
                }
            },
            { name: 'member_designation_changed',
                label: 'Does this represent a change from previous years?',
                type: "radio-group",
                options: yesNoOptions
            }
        ]
    },
]
