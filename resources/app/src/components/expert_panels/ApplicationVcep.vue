<template>
    <div>
        <div class="application-step" id="definition">
            <app-section title="Basic Information" name="basicInfo">
                <group-form 
                    :group="group" ref="groupForm"
                />
            </app-section>
            <app-section v-if="group" title="Membership" name="membership">
                <p>
                    Expert Panels are expected to represent the diversity of expertise and backgrounds in the field and should refer to 
                    <a href="https://clinicalgenome.org/site/assets/files/3635/variant_curation_expert_panel_vcep_protocol_version_9-2.pdf" target="vcep-protocol">Section 2.1 of the VCEP Protocol</a> and the <a href="https://diversity.nih.gov/sites/coswd/files/images/SWD_Toolkit_Interactive-updated_508.pdf" target="nih-workforce-diversity">NIH Scientific Workforce Diversity Toolkit</a> for guidance to complete the Member List below. Please list the VCEP Chair(s) and Coordinator(s) first.
                </p>
                <member-list :group="group" />
                <hr>
                <membership-description-form :editing="true" />
            </app-section>
            <app-section title="Scope of Work" name="scope">
                <vcep-gene-list :group="group" ref="geneList" />
                <hr>
                <scope-description-form />
            </app-section>
            <app-section title="Reanalysis & Discrepancy Resolution">
                <attestation-reanalysis></attestation-reanalysis>
            </app-section>
            <app-section title="NHGRI Data Availability">
                <attestation-nhgri></attestation-nhgri>
            </app-section>
        </div>

        <div class="application-step" id="draft-specifications">
            <app-section title="Draft Specifications">
                <cspec-summary></cspec-summary>
            </app-section>
        </div>

        <div class="application-step" id="pilot-specifications">
            <app-section title="Pilot Specifications">
                <cspec-summary />
            </app-section>
        </div>

        <div class="application-step" id="sustained-curation">
            <app-section title="Curation and Review Process">
                <vcep-ongoing-plans-form />
            </app-section>

            <app-section title="Example Evidence Summaries">
                <evidence-summary-list />
            </app-section>

            <app-section title="Designation of Biocurators, Biocurator Trainer(s) and Core Approval Members">
                <member-designation-form ref="designationForm" />
            </app-section>
        </div>

        <button class="btn btn-xl" @click="submit" :disabled="requirementsUnmet">
            Submit for Approval
        </button>
    </div>
</template>
<script>
import {isValidationError} from '@/http'
import ApplicationSection from '@/components/expert_panels/ApplicationSection'
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

export default {
    name: 'ApplicationCcep',
    components: {
        'app-section': ApplicationSection,
        AttestationNhgri,
        AttestationReanalysis,
        CspecSummary,
        EvidenceSummaryList,
        GroupForm,
        MemberDesignationForm,
        MemberList,
        MembershipDescriptionForm,
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
        meetsRequirements () {
            return false;
        },
        requirementsUnmet () {
            return !this.meetsRequirements;
        },
        currentStep () {
            return this.group.expert_panel.current_step;
        }
    },
    methods: {
        async save() {
            console.log('ApplicationVcep.methods.save', Object.keys(this.$refs))
            const promises = Object.keys(this.$refs).map(key => this.$refs[key].save());

            try {
                await Promise.all(promises);
            } catch (error) {
                if (isValidationError(error)) {
                    this.errors = error.response.data.errors;
                    return;
                }
                throw error;
            }
        }
    }
}
</script>