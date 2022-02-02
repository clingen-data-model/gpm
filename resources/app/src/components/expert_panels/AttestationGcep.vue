<template>
    <div>
        <p>
            The Gene Curation Expert Panel (GCEP) leaders(s) will complete the checkbox attestations document below on behalf of the GCEP. 
        </p>
        <ul class="ml-4 mt-2">
            <li>
                <input-row :errors="errors.utilize_gt" :hide-label="true">
                    <checkbox v-model="group.expert_panel.utilize_gt" :disabled="disabled" @update:modelValue="emitUpdate(), checkCompleteness()">
                        This GCEP will utilize the ClinGen Gene Tracker for documentation of all precuration information, consistent with the current Lumping and Splitting working group guidance, for gene-disease relationships.
                    </checkbox>
                </input-row>
            </li>
            <li>
                <input-row :errors="errors.utilize_gci" :hide-label="true">
                    <checkbox 
                        v-model="group.expert_panel.utilize_gci" 
                        :disabled="disabled"  
                        @update:modelValue="emitUpdate(), checkCompleteness()"
                    >
                        This GCEP will utilize the ClinGen Gene Curation Interface for documentation of all gene-disease validity classifications.
                    </checkbox>
                </input-row>
            </li>
            <li>
                <input-row :errors="errors.curations_publicly_available" :hide-label="true">
                    <checkbox 
                        v-model="group.expert_panel.curations_publicly_available" 
                        :disabled="disabled"
                        @update:modelValue="emitUpdate(), checkCompleteness()"
                    >
                        All curations completed by this group will be made publicly available through the ClinGen website immediately upon completion.
                    </checkbox>
                </input-row>
            </li>
            <li>
                <input-row :errors="errors.pub_policy_reviewed" :hide-label="true">
                    <checkbox v-model="group.expert_panel.pub_policy_reviewed" :disabled="disabled" @update:modelValue="emitUpdate(), checkCompleteness()">
                        The <a href="https://clinicalgenome.org/site/assets/files/3752/clingen_publication_policy_apr2019.pdf" target="pub-policy">ClinGen publication policy</a> has been reviewed and a manuscript concept sheet will be submitted to the NHGRI and  ClinGen Steering Committee before the group prepares a publication for submission.
                    </checkbox>
                </input-row>
            </li>
            <li>
                <input-row :errors="errors.draft_manuscripts" :hide-label="true">
                    <checkbox v-model="group.expert_panel.draft_manuscripts" :disabled="disabled" @update:modelValue="emitUpdate(), checkCompleteness()">
                        Draft manuscripts will be submitted to the ClinGen Gene Curation WG for review prior to submission. Email: <a href="mailto:genecuration@clinicalgenome.org">mailto:genecuration@clinicalgenome.org</a>
                    </checkbox>
                </input-row>
            </li>
            <li>
                <input-row :errors="errors.recuration_process_review" :hide-label="true">
                    <checkbox v-model="group.expert_panel.recuration_process_review" :disabled="disabled" @update:modelValue="emitUpdate(), checkCompleteness()">
                        The ClinGen Gene-Disease Validity Recuration process has been reviewed, link found <a href="https://clinicalgenome.org/site/assets/files/2164/clingen_standard_gene-disease_validity_recuration_procedures_v1.pdf">here</a>.
                    </checkbox>
                </input-row>
            </li>
        </ul>

        <p>
            Biocurators are expected to become familiar with the ClinGen training materials located on <a href="https://clinicalgenome.org/docs/?doc-type=training-materials#list_documentation_table" target="clinicalgenome">clinicalgenome.org</a> website. Biocurators are requested to join the mailing list for ClinGen Biocurator Working Group, and expected to attend those calls that focus on gene curation SOP and/or framework updates.
        </p>

        <ul class="ml-4 mt-2">
            <li>
                <input-row :errors="errors.biocurator_training" :hide-label="true">
                    <checkbox v-model="group.expert_panel.biocurator_training" :disabled="disabled" @update:modelValue="emitUpdate(), checkCompleteness()">
                        Biocurators have received all appropriate training. 
                    </checkbox>
                </input-row>
            </li>
            <li>
                <checkbox v-model="gci_training" :disabled="disabled" @update:modelValue="emitUpdate(), checkCompleteness()">
                    Biocurators are trained on the use of the Gene Curation Interface (GCI).
                </checkbox>
                <input-row v-model="group.expert_panel.gci_training_date" @update:modelValue="emitUpdate(), checkCompleteness()"
                    v-show="gci_training" 
                    :errors="gciTrainingErrors"
                    label="Date Trained"
                    type="date"
                    class="ml-6"
                />
            </li>
            <li>
                <input-row :errors="errors.biocurator_mailing_list" :hide-label="true">
                    <checkbox v-model="group.expert_panel.biocurator_mailing_list" :disabled="disabled" @update:modelValue="emitUpdate(), checkCompleteness()">
                        Biocurators have joined the Biocurator WG mailing list.
                        <br>The calls occur on the 2nd and 4th Thursdays from 12-1pm.
                    </checkbox>
                </input-row>
            </li>
        </ul>
    </div>
</template>
<script>
import {api, isValidationError} from '@/http'

export default {
    name: 'AttestationGcep',
    props: {
        disabled: {
            type: Boolean,
            required: false,
            default: false
        }
    },
    emits: [
        'update'
    ],
    data() {
        return {
            errors: {},
            gci_training: false
        }
    },
    computed: {
        group: {
            get () {
                return this.$store.getters['groups/currentItemOrNew'];
            },
            set (value) {
                this.$store.commit('groups/addItem', value)
            }
        },
        gciTrainingErrors () {
            const trainingErrors = this.errors.gci_training || [];
            const dateErrors = this.errors.gci_training_date || [];
            return [...trainingErrors, ...dateErrors];
        },
        gci_training_comp () {
            return Boolean(this.group.expert_panel.gci_training_date)
        }
    },
    watch: {
        group: {
            immediate: true,
            handler: function (to) {
                this.gci_training = Boolean(to.expert_panel.gci_training_date)
            }
        },
    },
    methods: {
        async save () {
            try {
                this.errors = {};
                await api.post(`/api/groups/${this.group.uuid}/application/attestations/gcep`,
                this.group.expert_panel.attributes)
            } catch (error) {
                if (isValidationError(error)) {
                    this.errors = error.response.data.errors;
                }
            }
        },
        emitUpdate () {
            this.$emit('update');
        },
        checkCompleteness () {
            if (this.group.expert_panel.utilize_gt
                && this.group.expert_panel.utilize_gci
                && this.group.expert_panel.curations_publicly_available
                && this.group.expert_panel.pub_policy_reviewed
                && this.group.expert_panel.draft_manuscripts
                && this.group.expert_panel.recuration_process_review
                && this.group.expert_panel.biocurator_training
                && this.group.expert_panel.gci_training_date !== null
                && this.group.expert_panel.biocurator_mailing_list
                && this.group.expert_panel.gcep_attestation_date === null
            ) {
                this.group.expert_panel.gcep_attestation_date = new Date();
                return;
            }
            this.group.expert_panel.gcep_attestation_date = null;
        }
    },
}
</script>