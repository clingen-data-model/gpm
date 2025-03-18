<template>
    <div>
        <p v-if="group.is_vcep">
            Curated variants and genes are expected to be approved and posted
            for the community as soon as possible as described in Section 2.4 of
            the <variant-panel-protocol-link />. Note that upon approval, a
            {{ group.type.display_name }} must finalize their set of variants
            for upload to the ClinGen Evidence Repository within 30 days.
        </p>
        <p v-if="group.is_scvcep">
            Curated variants and genes are expected to be published to CIViC in
            a timely manner, as described in the
            <variant-panel-protocol-link />. Note that upon approval, an
            {{ group.type.display_name }} must work to submit all approved
            {{ group.type.display_name }} Assertions to ClinVar within 30 days.
        </p>
        <p v-if="group.is_gcep">
            Curated genes and variants are expected to be approved and posted
            for the community as soon as possible and should not wait for the
            publication of a manuscript.
        </p>

        <p class="my-4">
            <input-row label="" :vertical="true">
                <checkbox
                    :disabled="disabled"
                    v-model="attestation"
                    id="nhgri-checkbox"
                    :label="checkboxLabel"
                />
            </input-row>
        </p>

        <p v-if="group.is_vcep_or_scvcep">
            Please review the
            <publication-policy-link />
            and refer to guidance on submissions to a preprint server (e.g.
            bioRxiv or medRxiv).
        </p>
        <p v-if="group.is_gcep">
            <em
                >It is expected that, whenever possible, Expert Panel
                manuscripts will be pre-published (e.g. medRXiv) . If the
                authors do not anticipate submitting their manuscript to a
                prepublication resource they must provide a written
                justification.</em
            >
        </p>
    </div>
</template>
<script>
import api from '@/http/api';
import is_validation_error from '../../http/is_validation_error';
import VariantPanelProtocolLink from "../links/VariantPanelProtocolLink.vue";

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
            get() {
                return this.$store.getters['groups/currentItemOrNew'];
            },
            set(value) {
                this.$store.commit('groups/addItem', value);
            }
        },
        attestation: {
            get() {
                return Boolean(this.group.expert_panel.nhgri_attestation_date);
            },
            set(value) {
                if (value) {
                    this.group.expert_panel.nhgri_attestation_date = new Date();
                } else {
                    this.group.expert_panel.nhgri_attestation_date = null;
                }
                this.$emit('update');
            }
        },
        checkboxLabel() {
            if (this.group.is_vcep) {
                return "I understand that once a variant is approved in the VCI it will become publicly available in the Evidence Repository. They should not be held for publication."
            }

            return "Please check box to confirm your understanding that once a gene is approved in the GCI, the group should utilize the “publish” functionality within the GCI to make the curation publicly available on the ClinGen website (https://clinicalgenome.org/). They should not be held for publication."
        }
    },
    methods: {
        async save() {
            if (this.attestation) {
                try {
                    await api.post(`/api/groups/${this.group.uuid}/application/attestations/nhgri`, { attestation: this.attestation });
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
