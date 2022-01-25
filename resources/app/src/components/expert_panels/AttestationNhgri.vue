<template>
    <div>
        <p>
            Curated variants and genes are expected to be approved and posted for the community as soon as possible as described in Section 2.4 
            <a 
                class="link" 
                target="vcep-protocol" 
                href="https://clinicalgenome.org/site/assets/files/3635/variant_curation_expert_panel_vcep_protocol_version_9-2.pdf"
            >
                VCEP Protocol
            </a>. 
            Note that upon approval, a VCEP must finalize their set of variants for upload to the ClinGen Evidence Repository within 30 days.
        </p>

        <p class="my-4">
            <input-row label="" :vertical="true">
                <checkbox 
                    :disabled="disabled" 
                    v-model="attestation" 
                    id="nhgri-checkbox" 
                    label="I understand that once a variant is approved in the VCI it will become publicly available in the Evidence Repository. They should not be held for publication."
                />
            </input-row>
        </p>

        <p>
            Please review the 
            <a
                class="link"
                target="pub-policy"
                href="https://clinicalgenome.org/site/assets/files/6737/clingen_publication_policy_june2021_final.pdf"
            >
                ClinGen Publication Policy
            </a> 
            and refer to guidance on submissions to a preprint server (e.g. bioRxiv or medRxiv).
        </p>
    </div>
</template>
<script>
import api from '@/http/api';
import is_validation_error from '../../http/is_validation_error';

export default {
    name: 'NHGRIDataAvailability',
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
        }
    },
    computed: {
        group: {
            get () {
                return this.$store.getters['groups/currentItemOrNew'];
            },
            set (value) {                
                this.$store.commit('groups/addItem', value);
            }
        },
        attestation: {
            get () {
                return Boolean(this.group.expert_panel.nhgri_attestation_date);
            },
            set (value) {
                if (value) {
                    this.group.expert_panel.nhgri_attestation_date = new Date();
                } else {
                    this.group.expert_panel.nhgri_attestation_date = null;
                }
                this.$emit('update');                
            }
        }
    },
    methods: {
        async save () {
            if (this.attestation) {
                try {
                    await api.post(`/api/groups/${this.group.uuid}/application/attestations/nhgri`, {'attestation': this.attestation})
                } catch (error) {
                    if (is_validation_error(error)) {
                        this.errors = error.response.data.errors
                    }
                }
            }
        }
    }
}
</script>