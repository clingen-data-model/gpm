<template>
    <div>
        <h3>NHGRI Data Availability</h3>
        <p>Curated variants and genes are expected to be approved and posted for the community as soon as possible as described in Section 2.4 <a class="link" href="https://clinicalgenome.org/site/assets/files/3635/variant_curation_expert_panel_vcep_protocol_version_9-2.pdf">VCEP Protocol</a>. Note that upon approval, a VCEP must finalize their set of variants for upload to the ClinGen Evidence Repository within 30 days.</p>

        <p class="my-4">
            <label class="flex space-x-2">
                <div class="pt-1"><input type="checkbox" v-model="attestation"></div>
                <div class="font-bold">I understand that once a variant is approved in the VCI it will become publicly available in the Evidence Repository. They should not be held for publication.</div>
            </label>
        </p>

        <p>
            Please review the <a class="link" href="https://clinicalgenome.org/site/assets/files/6737/clingen_publication_policy_june2021_final.pdf">ClinGen Publication Policy</a> and refer to guidance on submissions to a preprint server (e.g. bioRxiv or medRxiv).
        </p>
    </div>
</template>
<script>
export default {
    name: 'NHGRIDataAvailability',
    props: {
        
    },
    data() {
        return {
            attestation: null
        }
    },
    computed: {

    },
    methods: {

    }
}
</script>