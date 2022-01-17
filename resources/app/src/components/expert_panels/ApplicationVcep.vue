<template>
    <div>
        <application-step 
            id="definition" 
            title="Group Definition"
            :disabled="group.expert_panel.hasPendingSubmission"
        >
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

        <!-- <application-step 
            id="specifications-development" 
            title="Specifications Development" 
            :disabled="group.expert_panel.current_step < 2 || group.expert_panel.hasPendingSubmission"
            :no-submit="true"
        >
            <app-section>
                <cspec-summary></cspec-summary>
            </app-section>
        </application-step>
        -->
        <application-step 
            id="draft-specifications" 
            title="Draft Specifications" 
            :disabled="group.expert_panel.current_step < 2  || group.expert_panel.hasPendingSubmission"
            :no-submit="true"
        >
            <app-section>
                <cspec-summary></cspec-summary>
            </app-section>
        </application-step>

        <application-step 
            id="pilot-specifications" 
            title="Pilot Specifications"
            :disabled="group.expert_panel.current_step < 3  || group.expert_panel.hasPendingSubmission"
            :no-submit="true"
        >
            <app-section>
                <cspec-summary></cspec-summary>
            </app-section>
        </application-step>

        <application-step 
            id="sustained-curation" 
            title="Sustained Curation"
            :disabled="group.expert_panel.current_step < 4 || group.expert_panel.hasPendingSubmission"
        >
            <app-section title="Plans for Ongoing Review and Reanalysis and Discrepancy Resolution" id="curationReviewProcess">
                <vcep-ongoing-plans-form />
            </app-section>

            <app-section title="Example Evidence Summaries" id="evidenceSummaries">
                <evidence-summary-list />
            </app-section>

            <app-section title="Member Designation" id="designations">
                <member-designation-form ref="designationForm" />
            </app-section>
        </application-step>
    </div>
</template>
<script>
import {VcepApplication} from '@/domain'
import {isValidationError} from '@/http'
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

export default {
    name: 'ApplicationVcep',
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
        ScopeDescriptionForm,
        VcepGeneList,
        VcepOngoingPlansForm,
    },
    emits: [
        'autosaved',
        'saving',
        'saved'
    ],
    data () {
        return {
            application: VcepApplication,
            AutoSaveInterval: null,
            autosaveTime: 10000,
            saving: false
        }
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
    },
    methods: {
        async save() {
            this.$emit('saving');

            const promises = Object.keys(this.$refs).map(key => this.$refs[key].save());
            promises.push(this.saveUpdates());

            try {
                await Promise.all(promises);
                this.$emit('saved');
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
                return this.$store.dispatch('groups/saveApplicationData', this.group);
            }
        },
        async autosave () {
            if (this.applicationIsDirty()) {
                await this.save();
                this.$emit('autosaved');
                return;
            }
        },
        async startAutoSave () {
            this.AutoSaveInterval = setInterval(() => this.autosave(), this.autosaveTime)
        },
        stopAutoSave() {
            clearInterval(this.AutoSaveInterval);
        },
        applicationIsDirty () {
            return  this.group.expert_panel.isDirty() 
                || this.group.isDirty()
        }
    },
    // mounted() {
    //     this.startAutoSave();
    // },
    // beforeUnmount() {
    //     this.stopAutoSave();
    // },
}
</script>
