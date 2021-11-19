<template>
    <div>
        <p>
            The Gene Curation Expert Panel (GCEP) leaders(s) will complete the checkbox attestations document below on behalf of the GCEP. 
        </p>
        <ul class="ml-4 mt-2">
            <li>
                <input-row :errors="errors.utilize_gt" :hide-label="true">
                    <checkbox v-model="attestation.utilize_gt">
                        This GCEP will utilize the ClinGen Gene Tracker for documentation of all precuration information, consistent with the current Lumping and Splitting working group guidance, for gene-disease relationships.
                    </checkbox>
                </input-row>
            </li>
            <li>
                <input-row :errors="errors.utilize_gci" :hide-label="true">
                    <checkbox v-model="attestation.utilize_gci">
                        All curations completed by this group will be made publicly available through the ClinGen website immediately upon completion.
                    </checkbox>
                </input-row>
            </li>
            <li>
                <input-row :errors="errors.curations_publicly_available" :hide-label="true">
                    <checkbox v-model="attestation.curations_publicly_available">
                        VCEPs are expected to re-review any LB classifications when new evidence is available or when requested by the public via the ClinGen website.
                    </checkbox>
                </input-row>
            </li>
            <li>
                <input-row :errors="errors.pub_policy_reviewed" :hide-label="true">
                    <checkbox v-model="attestation.pub_policy_reviewed">
                        The <a href="https://clinicalgenome.org/site/assets/files/3752/clingen_publication_policy_apr2019.pdf" target="pub-policy">ClinGen publication policy</a> has been reviewed and a manuscript concept sheet will be submitted to the NHGRI and  ClinGen Steering Committee before the group prepares a publication for submission.
                    </checkbox>
                </input-row>
            </li>
            <li>
                <input-row :errors="errors.draft_manuscripts" :hide-label="true">
                    <checkbox v-model="attestation.draft_manuscripts">
                        Draft manuscripts will be submitted to the ClinGen Gene Curation WG for review prior to submission. Email: <a href="mailto:genecuration@clinicalgenome.org">mailto:genecuration@clinicalgenome.org</a>
                    </checkbox>
                </input-row>
            </li>
            <li>
                <input-row :errors="errors.recuration_process_review" :hide-label="true">
                    <checkbox v-model="attestation.recuration_process_review">
                        The ClinGen Gene-Disease Validity Recuration process has been reviewed, link found <a href="https://clinicalgenome.org/site/assets/files/2164/clingen_standard_gene-disease_validity_recuration_procedures_v1.pdf">here</a>.
                    </checkbox>
                </input-row>
            </li>

        </ul>

        <p>
            Biocurators are expected to become familiar with the ClinGen training materials located on the clinicalgenome.org website. Biocurators are requested to join the mailing list for ClinGen Biocurator Working Group WG, and expected to attend those calls that focus on gene curation SOP and/or framework updates.
        </p>
        <ul class="ml-4 mt-2">
            <li>
                <input-row :errors="errors.biocurator_training" :hide-label="true">
                    <checkbox v-model="attestation.biocurator_training">
                        Biocurators have received all appropriate training. 
                    </checkbox>
                </input-row>
            </li>
            <li>
                <input-row :errors="gciTrainingErrors" :hide-label="true">
                    <checkbox v-model="gci_training">
                        Biocurators are trained on the use of the Gene Curation Interface (GCI).
                        <transition name="slide-fade-down"><label class="block" v-if="gci_training">
                            Date: <date-input type="text" v-model="attestation.gci_training_date" /></label>
                        </transition>
                    </checkbox>
                </input-row>
            </li>
            <li>
                <input-row :errors="errors.biocurator_mailing_list" :hide-label="true">
                    <checkbox v-model="attestation.biocurator_mailing_list">
                        Biocurators have joined the Biocurator WG mailing list.
                        <br>The calls occur on the 2nd and 4th Thursdays from 12-1pm.
                    </checkbox>
                </input-row>
            </li>
        </ul>
        <button class="btn" @click="submit" v-if="showSubmit">Submit</button>
    </div>
</template>
<script>
import api from '@/http/api'
import is_validation_error from '../../http/is_validation_error'

export default {
    name: 'AttestationGcep',
    props: {
        group: {
            type: Object,
            required: true
        },
        showSubmit: {
            type: Boolean,
            required: false,
            default: false
        }
    },
    data() {
        return {
            errors: {},
            attestation: {},
            gci_training: null
        }
    },
    computed: {
        gciTrainingErrors () {
            const trainingErrors = this.errors.gci_training ?? [];
            const dateErrors = this.errors.gci_training_date ?? [];
            return [...trainingErrors, ...dateErrors];
        }
    },
    watch: {
        group: {
            handler() {
                this.syncAttestation();
            },
            immediate: true
        }
    },
    methods: {
        initAttestation () {
            console.log('initAttestation')
            this.attestation = {
                utilize_gt: false,
                utilize_gci: false,
                curations_publicly_available: false,
                pub_policy_reviewed: false,
                draft_manuscripts: false,
                biocurator_training: false,
                gci_training_date: null,
                biocurator_mailing_list: false,
            },
            this.gci_training = null;
        },
        syncAttestation () {
            if (this.group.expert_panel) {
                this.attestation = {
                    utilize_gt: this.group.expert_panel.utilize_gt,
                    utilize_gci: this.group.expert_panel.utilize_gci,
                    curations_publicly_available: this.group.expert_panel.curations_publicly_available,
                    pub_policy_reviewed: this.group.expert_panel.pub_policy_reviewed,
                    draft_manuscripts: this.group.expert_panel.draft_manuscripts,
                    biocurator_training: this.group.expert_panel.biocurator_training,
                    gci_training_date: this.group.expert_panel.gci_training_date,
                    biocurator_mailing_list: this.group.expert_panel.biocurator_mailing_list,
                }
                this.gci_training = Boolean(this.group.expert_panel.gci_training_date);
                return;
            }

            this.initAttestation();
        },
        async submit () {
            try {
                this.errors = {};
                await api.post(`/api/groups/${this.group.uuid}/application/attestations/gcep`, this.attestation)
            } catch (error) {
                if (is_validation_error(error)) {
                    this.errors = error.response.data.errors;
                }
            }
        }
    },
}
</script>