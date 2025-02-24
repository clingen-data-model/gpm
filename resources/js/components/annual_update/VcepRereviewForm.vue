<script>
import mirror from '@/composables/setup_working_mirror'

export default {
    name: 'VcepRereviewForm',
    props: {
        ...mirror.props,
        errors: {
            type: Object,
            required: true
        },
    },
    emits: [ ...mirror.emits ],
    computed: {
        isComplete () {
            return Boolean(this.modelValue.completed_at)
        }
    },
    setup(props, context) {
        const {workingCopy} = mirror.setup(props, context);
        return {
            workingCopy
        }
    }
}
</script>
<template>
    <div>
        <pre>{{ isComplete }}</pre>
        <p>
            Variant Curation Expert Panels are expected to keep their variant interpretations up-to-date and to expedite the re-review of variants that have a conflicting assertion submitted to ClinVar after the Expert Panel submission (guidelines for recuration timeline provide in the VCEP protocol: https://clinicalgenome.org/docs/clingen-variant-curation-expert-panel-vcep-protocol/). . Please answer the following question concerning recuration:

        </p>
        <input-row 
            v-model="workingCopy.data.rereview_discrepencies"
            type="large-text"
            :disabled="isComplete"
            label="Are you receiving and/or using the VCEP Variant Tracker reports to aid in the recuration work? Please describe below  ">
        </input-row>

    
    </div>
</template>