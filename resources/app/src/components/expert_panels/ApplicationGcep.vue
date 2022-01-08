<template>
    <div class="relative">
        <application-step id="definition" :disabled="group.expert_panel.hasPendingSubmission">
            <app-section title="Basic Information" id="basicInfo">
                <group-form 
                    :group="group" ref="groupForm"
                />
            </app-section>
            <app-section v-if="group" title="Membership" id="membership">
                <p>
                    Expert Panels are expected to represent the diversity of expertise in the field, including all major areas of expertise (clinical, diagnostic laboratory, and basic research).  Membership should include representation from three or more institutions and will encompass disease/gene expert members as well as biocurators. Biocurators do not have to be gene/disease experts and will be primarily responsible for assembling the available evidence for subsequent expert member review. For role, suggested examples include: primary biocurator, expert reviewer, etc.
                </p>
                <member-list :group="group" />
            </app-section>
            <app-section title="Scope of Work" id="scope">
                <p>
                    It is expected that the expert panel will utilize 
                    <a target="lumping-splitting-guidelines"
                        href="https://clinicalgenome.org/working-groups/lumping-and-splitting/">ClinGen Lumping and Splitting guidance</a> 
                    during pre-curation and should use the 
                    <a target="gene-tracker" href="https://gene-tracker.clinicalgenome.org/">Gene Tracker</a>
                    to enter their precuration information. Focus should be on the canonical disease, and splitting into multiple phenotypes should be avoided. The precurations will be published to
                    <a href="https://clinicalgenome.org">clinicalgenome.org</a>.
                </p>
                
                <gcep-gene-list :group="group" ref="geneList" @geneschanged="genesChanged = true" />
                
                <hr>

                <scope-description-form />
            </app-section>

            <app-section title="Attestations" id="attestations">
                <attestation-gcep />
            </app-section>

            <app-section id="curationReviewProcess" title="Plans for Ongoing Gene Review and Reanalysis and Discrepancy Resolution">
                <gcep-ongoing-plans-form />
            </app-section>

            <app-section title="NHGRI Data Availability" id="nhgri">
                <attestation-nhgri />
            </app-section>
        </application-step>
    </div>
</template>
<script>
import {errors, resetErrors, submitFormData} from '@/forms/form_factory'
import {isValidationError} from '@/http'

import ApplicationSection from '@/components/expert_panels/ApplicationSection'
import ApplicationStep from '@/components/expert_panels/ApplicationStep'
import AttestationGcep from '@/components/expert_panels/AttestationGcep'
import AttestationNhgri from '@/components/expert_panels/AttestationNhgri'
import GcepGeneList from '@/components/expert_panels/GcepGeneList';
import GroupForm from '@/components/groups/GroupForm'
import MemberList from '@/components/groups/MemberList';
import ScopeDescriptionForm from '@/components/expert_panels/ScopeDescriptionForm';
import GcepOngoingPlansForm from '@/components/expert_panels/GcepOngoingPlansForm';

export default {
    name: 'ApplicationGcep',
    components: {
        'app-section': ApplicationSection,
        ApplicationStep,
        AttestationGcep,
        AttestationNhgri,
        GcepGeneList,
        GroupForm,
        GcepOngoingPlansForm,
        MemberList,
        ScopeDescriptionForm,
    },
    data () {
        return {
            AutoSaveInterval: null,
            autosaveTime: 10000,
            genesChanged: false,
            saving: false,
        }
    },
    emits: [
        'autosaved',
        'saved',
        'saving',
    ],
    computed: {
        group: {
            get: function () {
                return this.$store.getters['groups/currentItemOrNew'];
            },
            set: function (value) {
                this.$store.commit('groups/addItem', value);
            }
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
                this.genesChanged = false;
            } catch (error) {
                if (isValidationError(error)) {
                    this.errors = error.response.data.errors;
                    return;
                }
                throw error;
            }
        },
        saveUpdates () {
            if (this.applicationIsDirty()) {
                return this.$store.dispatch('groups/saveApplicationData', this.group)
                        .then(() => {
                            this.$emit('saved');
                        });
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
                || this.genesChanged
        }

    },
    setup () {
        return {
            errors, 
            resetErrors, 
            submitFormData
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