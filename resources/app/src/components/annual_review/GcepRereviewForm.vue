<template>
    <div>
        <p>Gene Curation Expert Panels are expected to review current clinical validity classifications for their approved genes based on the guidance provided in the <a target="gcep-recuration-process" href="https://clinicalgenome.org/site/assets/files/2164/clingen_standard_gene-disease_validity_recuration_procedures_v1.pdf">Gene-Disease Validity Recuration Process document</a>. Please indicate the following:</p>

        <input-row 
            :disabled="isComplete"
            label="Have you begun recuration?"
            v-model="workingCopy.data.recuration_begun"
            type="radio-group"
            :options="[{value:'yes'},{value:'no'}]"
            :errors="errors.recuration_begun" 
            vertical
        />

        <input-row 
            :disabled="isComplete"
            v-model="workingCopy.data.recuration_designees"
            :errors="errors.recuration_designees"
            type="large-text"
            placeholder="First Last, Email"
            vertical
        >
            <template v-slot:label>
                The GCEP recuration designee(s) is designated to monitor for recuration updates, on a yearly basis, according to the <a target="gcep-recuration-process" href="https://clinicalgenome.org/site/assets/files/2164/clingen_standard_gene-disease_validity_recuration_procedures_v1.pdf">Gene-Disease Validity Recuration Process document</a>. Please list the name(s) of your GCEP recuration designee(s).
            </template>
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
    computed: {
        isComplete () {
            return Boolean(this.modelValue.completed_at)
        }
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