<script>

const SC_VCEP_PROTOCOL_URL = 'https://clinicalgenome.org/docs/guidelines-for-applying-for-variant-or-gene-curation-expert-panel-status/';
const VCEP_PROTOCOL_URL = 'https://www.clinicalgenome.org/docs/clingen-variant-curation-expert-panel-vcep-protocol/';

export default {
    name: 'VcepProtocolLink',
    computed: {
        group() {
            return this.$store.getters['groups/currentItemOrNew'];
        },
        url() {
            if (this.group.type.is_somatic_cancer) {
                return SC_VCEP_PROTOCOL_URL;
            }
            return VCEP_PROTOCOL_URL;
        },
        defaultLabel() {
            if (this.group.type.is_somatic_cancer) {
                return 'SC-VCEP Protocol';
            }
            return 'VCEP Protocol';
        }
    }
}
</script>

<template>
        <a
        :href="url"
        target="variant-panel-protocol"
    >
        <slot>{{ defaultLabel }}</slot>
    </a>
</template>
