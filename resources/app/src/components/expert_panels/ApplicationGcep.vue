<template>
    <div>
        <app-section title="Basic Information">
            <group-form 
                :group="group" ref="groupForm"
             />
        </app-section>
        <app-section v-if="group" title="Membership">
            <p>
                Expert Panels are expected to represent the diversity of expertise in the field, including all major areas of expertise (clinical, diagnostic laboratory, and basic research).  Membership should include representation from three or more institutions and will encompass disease/gene expert members as well as biocurators. Biocurators do not have to be gene/disease experts and will be primarily responsible for assembling the available evidence for subsequent expert member review. For role, suggested examples include: primary biocurator, expert reviewer, etc.
            </p>
            <member-list :group="group" />
        </app-section>
        <app-section title="Scope of Work">
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
            <scope-description-form v-model:group="workingCopy" />
        </app-section>

        <app-section title="Attestations">
            <attestation-gcep v-model:group="workingCopy" ref="gcepAttestation" />
            <gcep-ongoing-plans-form v-model:group="workingCopy" ref="ongongPlans" />
        </app-section>

        <app-section title="NHGRI Data Availability">
            <attestation-nhgri v-model:group="workingCopy" ref="nhgri"></attestation-nhgri>
        </app-section>
        <button class="btn btn-xl" @click="submit" :disabled="requirementsUnmet">
            Submit for Approval
        </button>

    </div>
</template>
<script>
import {errors, resetErrors, submitFormData} from '@/forms/form_factory'

import ApplicationSection from '@/components/expert_panels/ApplicationSection'
import AttestationGcep from '@/components/expert_panels/AttestationGcep'
import AttestationNhgri from '@/components/expert_panels/AttestationNhgri'
import BasicInfoForm from '@/components/expert_panels/BasicInfoForm'
import GcepGeneList from '@/components/expert_panels/GcepGeneList';
import GroupForm from '@/components/groups/GroupForm'
import MemberList from '@/components/groups/MemberList';
import ScopeDescriptionForm from '@/components/expert_panels/ScopeDescriptionForm';
import GcepOngoingPlansForm from '@/components/expert_panels/GcepOngoingPlansForm';

import Group from '@/domain/group'

export default {
    name: 'ApplicationGcep',
    components: {
        'app-section': ApplicationSection,
        AttestationGcep,
        AttestationNhgri,
        BasicInfoForm,
        GcepGeneList,
        GroupForm,
        GcepOngoingPlansForm,
        MemberList,
        ScopeDescriptionForm,
    },
    props: {
        group: {
            type: Object,
            required: true,
            default: () => ({})
        },
    },
    data() {
        return {
            workingCopy: new Group,
            componentData: {}
        }
    },
    computed: {
        meetsRequirements () {
            return false;
        },
        requirementsUnmet () {
            return !this.meetsRequirements;
        }
    },
    watch: {
        group: {
            handler: function (to, from) {
                if (to != from) {
                    this.workingCopy = to.clone()
                }
            },
            immediate: true
        }
    },
    methods: {
        async save () {
            this.$emit('update:group', this.workingCopy);
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