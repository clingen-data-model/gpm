<template>
    <div>
        <app-section title="Basic Information" name="basicInfo">
            <group-form 
                :group="group" ref="groupForm"
             />
        </app-section>
        <app-section v-if="group" title="Membership" name="membership">
            <p>
                Expert Panels are expected to represent the diversity of expertise in the field, including all major areas of expertise (clinical, diagnostic laboratory, and basic research).  Membership should include representation from three or more institutions and will encompass disease/gene expert members as well as biocurators. Biocurators do not have to be gene/disease experts and will be primarily responsible for assembling the available evidence for subsequent expert member review. For role, suggested examples include: primary biocurator, expert reviewer, etc.
            </p>
            <member-list :group="group" />
        </app-section>
        <app-section title="Scope of Work" name="scope">
            <p>
                It is expected that the expert panel will utilize 
                <a target="lumping-splitting-guidelines"
                    href="https://clinicalgenome.org/working-groups/lumping-and-splitting/">ClinGen Lumping and Splitting guidance</a> 
                during pre-curation and should use the 
                <a target="gene-tracker" href="https://gene-tracker.clinicalgenome.org/">Gene Tracker</a>
                to enter their precuration information. Focus should be on the canonical disease, and splitting into multiple phenotypes should be avoided. The precurations will be published to
                <a href="https://clinicalgenome.org">clinicalgenome.org</a>.
            </p>
            
            <gcep-gene-list :group="group" ref="geneList" />
            
            <hr>

            <p>Describe the scope of work of the Expert Panel: disease area(s) of focus and gene list being addressed.</p>
            <scope-description-form />
        </app-section>

        <app-section title="Attestations" name="attestations">
            <attestation-gcep ref="gcepAttestation" />
            <gcep-ongoing-plans-form />
        </app-section>

        <app-section title="NHGRI Data Availability" name="nhgri">
            <attestation-nhgri ref="nhgri"></attestation-nhgri>
        </app-section>
        
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
import {errors, resetErrors, submitFormData} from '@/forms/form_factory'

import ApplicationSection from '@/components/expert_panels/ApplicationSection'
import AttestationGcep from '@/components/expert_panels/AttestationGcep'
import AttestationNhgri from '@/components/expert_panels/AttestationNhgri'
import GcepGeneList from '@/components/expert_panels/GcepGeneList';
import GroupForm from '@/components/groups/GroupForm'
import MemberList from '@/components/groups/MemberList';
import ScopeDescriptionForm from '@/components/expert_panels/ScopeDescriptionForm';
import GcepOngoingPlansForm from '@/components/expert_panels/GcepOngoingPlansForm';
import RequirementsItem from '@/components/expert_panels/RequirementsItem'
import {GcepRequirements} from '@/domain'

const requirements = new GcepRequirements();

export default {
    name: 'ApplicationGcep',
    components: {
        'app-section': ApplicationSection,
        AttestationGcep,
        AttestationNhgri,
        GcepGeneList,
        GroupForm,
        GcepOngoingPlansForm,
        MemberList,
        ScopeDescriptionForm,
        RequirementsItem,
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
        async save () {
            // await this.$store.dispatch('updateGcepApplication', this.group.expert_panel);
            this.$emit('saved');
        },
    },
    setup (props, context) {
        return {
            errors, 
            resetErrors, 
            submitFormData
        }
    }
}
</script>
<style lang="postcss">
</style>