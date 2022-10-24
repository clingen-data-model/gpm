<script setup>
    import {ref} from 'vue';

    const dataPolicyAttestation = ref(false);

</script>
<template>
    <div>
        It is common that members of working groups and expert panels may share information from clinical or research testing that is only allowed to be shared for the purpose of informing the classification of a gene or variant and not for primary research or other uses. Therefore, please attest to the following statement to ensure shared data is treated with its intended use.

        <h3>Attestation:</h3>

        I will not utilize confidential information or documents to which I obtain access through my ClinGen efforts for any purpose outside of ClinGen activities without discussion with the provider of the data. This includes, but is not limited to, discussions or documents relating to the ClinGen group, lists of variants used within the expert panel, documents or datasets marked 'confidential', or unpublished data. If I have any reasonable doubt about whether particular information or a particular document is confidential, I will not utilize such information, documents, or data unless I have first clarified the situation with the appropriate leadership of the ClinGen group (e.g., chairs, grant liaisons) and obtained authorization.
        <input-row
            label=""
            type="radio-group"
            v-model="dataPolicyAttestation"
            :options="[{value: 1, label: 'Yes'}, {value: 0, label: 'No'}]"
            vertical
        />
    </div>
</template>
