<template>
    <div>
        <application-step id="definition" title="Group Definition">
            <app-section title="Basic Information" id="basicInfo">
                <group-form 
                    :group="group" ref="groupForm"
                />
            </app-section>
            <app-section v-if="group" title="Membership" id="membership">
                <p>
                    Expert Panels are expected to represent the diversity of expertise and backgrounds in the field and should refer to 
                    <a href="https://clinicalgenome.org/site/assets/files/3635/variant_curation_expert_panel_vcep_protocol_version_9-2.pdf" target="vcep-protocol">Section 2.1 of the VCEP Protocol</a> and the <a href="https://diversity.nih.gov/sites/coswd/files/images/SWD_Toolkit_Interactive-updated_508.pdf" target="nih-workforce-diversity">NIH Scientific Workforce Diversity Toolkit</a> for guidance to complete the Member List below. Please list the VCEP Chair(s) and Coordinator(s) first.
                </p>
                <member-list :group="group" />
                <hr>
                <membership-description-form :editing="true" />
            </app-section>
            <app-section title="Scope of Work" id="scope">
                <vcep-gene-list :group="group" ref="geneList" />
                <hr>
                <scope-description-form />
            </app-section>
            <app-section title="Reanalysis & Discrepancy Resolution" id="reanalysis">
                <attestation-reanalysis></attestation-reanalysis>
            </app-section>
            <app-section title="NHGRI Data Availability" id="nhgri">
                <attestation-nhgri></attestation-nhgri>
            </app-section>
        </application-step>

        <application-step id="draft-specifications" title="Draft Specifications">
            <app-section>
                <cspec-summary></cspec-summary>
            </app-section>
        </application-step>

        <application-step id="pilot-specifications" title="Pilot Specifications">
            <app-section>
                <cspec-summary></cspec-summary>
            </app-section>
        </application-step>

        <application-step id="sustained-curation" title="Sustained Curation">
            <app-section title="Plans for Ongoing Gene Review and Reanalysis and Discrepancy Resolution" id="curationReviewProcess">
                <vcep-ongoing-plans-form />
            </app-section>

            <app-section title="Example Evidence Summaries" id="evidenceSummaries">
                <evidence-summary-list />
            </app-section>

            <app-section title="Designation of Biocurators, Biocurator Trainer(s) and Core Approval Members" id="designations">
                <member-designation-form ref="designationForm" />
            </app-section>
        </application-step>

        <popper hover arrow>
            <template v-slot:content>
                <requirements-item  v-for="(req, idx) in evaledRequirements" :key="idx" :requirement="req" />
            </template>
            <div class="relative">
                <button class="btn btn-xl" @click="submit">
                    Submit for Approval
                </button>
                <!-- Add mask above button if requirements are unmet b/c vue3-popper doesn't respond to disabled components. -->
                <div v-if="requirementsUnmet" class="bg-white opacity-50 absolute top-0 bottom-0 left-0 right-0"></div>
            </div>
        </popper>
    </div>
</template>
<script>
import {isValidationError} from '@/http'
import {VcepRequirements} from '@/domain'
import ApplicationSection from '@/components/expert_panels/ApplicationSection'
import ApplicationStep from '@/components/expert_panels/ApplicationStep'
import AttestationNhgri from '@/components/expert_panels/AttestationNhgri'
import AttestationReanalysis from '@/components/expert_panels/AttestationReanalysis'
import CspecSummary from '@/components/expert_panels/CspecSummary'
import EvidenceSummaryList from '@/components/expert_panels/EvidenceSummaryList'
import GroupForm from '@/components/groups/GroupForm'
import MemberDesignationForm from '@/components/expert_panels/MemberDesignationForm';
import MemberList from '@/components/groups/MemberList';
import MembershipDescriptionForm from '@/components/expert_panels/MembershipDescriptionForm';
import ScopeDescriptionForm from '@/components/expert_panels/ScopeDescriptionForm';
import VcepGeneList from '@/components/expert_panels/VcepGeneList';
import VcepOngoingPlansForm from '@/components/expert_panels/VcepOngoingPlansForm';
import RequirementsItem from '@/components/expert_panels/RequirementsItem'

const requirements = new VcepRequirements();

export default {
    name: 'ApplicationCcep',
    components: {
        'app-section': ApplicationSection,
        ApplicationStep,
        AttestationNhgri,
        AttestationReanalysis,
        CspecSummary,
        EvidenceSummaryList,
        GroupForm,
        MemberDesignationForm,
        MemberList,
        MembershipDescriptionForm,
        RequirementsItem,
        ScopeDescriptionForm,
        VcepGeneList,
        VcepOngoingPlansForm,
    },
    computed: {
        group: {
            get: function () {
                return this.$store.getters['groups/currentItemOrNew'];
            },
            set: function (value) {
                this.$store.commit('groups/addItem', value);
            }
        },
        currentStep () {
            return this.group.expert_panel.current_step;
        },
        meetsRequirements () {
            return requirements.meetsRequirements(this.group);
        },
        requirementsUnmet () {
            return !requirements.meetsRequirements(this.group);
        },
        evaledRequirements () {
            return requirements.checkRequirements(this.group);
        }
    },
    methods: {
        async save() {
            const promises = Object.keys(this.$refs).map(key => this.$refs[key].save());
            promises.push(this.saveUpdates());

            try {
                await Promise.all(promises);
            } catch (error) {
                if (isValidationError(error)) {
                    this.errors = error.response.data.errors;
                    return;
                }
                throw error;
            }
        },
        saveUpdates () {
            if (this.group.expert_panel.isDirty()) {
                return this.$store.dispatch('groups/saveApplicationData', this.group)
                        .then(() => this.$store.commit('pushSuccess', 'Application updated'));
            }
        },
        submit () {
            console.log('submit');
        }
    }
}
</script>
