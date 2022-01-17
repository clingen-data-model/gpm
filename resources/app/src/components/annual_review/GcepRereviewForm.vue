<template>
    <div>
        <p>Gene Curation Expert Panels are expected to review current clinical validity classifications for their approved genes based on the guidance provided in the <a href="https://clinicalgenome.org/site/assets/files/2164/clingen_standard_gene-disease_validity_recuration_procedures_v1.pdf">Gene-Disease Validity Recuration Process document</a>. Please indicate the following:</p>

        <input-row label="Have you begun recuration?" :errors="errors.recuration_begun" vertical>
            <radio-button v-model="workingCopy.recuration_begun" :value="1" label="Yes"></radio-button>
            <radio-button v-model="workingCopy.recuration_begun" :value="0" label="No"></radio-button>
        </input-row>

        <input-row :errors="errors.recuration_designees" vertical>
            <template v-slot:label>
                The GCEP recuration designee(s) is designated to monitor for recuration updates, on a yearly basis, according to the <a href="https://clinicalgenome.org/site/assets/files/2164/clingen_standard_gene-disease_validity_recuration_procedures_v1.pdf">Gene-Disease Validity Recuration Process document</a>. Please list the name(s) of your GCEP recuration designee(s).
            </template>
            <textarea class="w-full" rows="5" v-model="workingCopy.recuration_designees" placeholder="First Last, Email"></textarea>
        </input-row>
    </div>
</template>
<script>
import mirror from '@/composables/setup_working_mirror'

export default {
    name: 'GroupRereviewForm',
    props: {
        ...mirror.props,
        errors: {
            type: Object,
            required: true
        },
    },
    emits: [ ...mirror.emits ],
    setup(props, context) {
        const {workingCopy} = mirror.setup(props, context);
        return {
            workingCopy
        }
    }
}
</script>