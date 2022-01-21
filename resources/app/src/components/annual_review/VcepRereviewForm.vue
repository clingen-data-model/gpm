<template>
    <div>
        <pre>{{isComplete}}</pre>
        <p>
            Variant Curation Expert Panels are expected to keep their variant interpretations up-to-date and to expedite the re-review of variants that have a conflicting assertion submitted to ClinVar after the Expert Panel submission. Please describe progress in each of the following activities and describe any reasons expectations have not been met.
        </p>
        <input-row 
            v-model="workingCopy.data.rereview_discrepencies"
            type="large-text"
            :disabled="isComplete"
            label="Discrepancies with Expert Panel classifications from a newly submitted ClinVar entry from a one star submitter or above (expected to be addressed within 6 months of posting)" :errors="errors.rereview_discrepencies">
        </input-row>

        <input-row 
            v-model="workingCopy.data.rereview_lp_vus"
            type="large-text"
            :disabled="isComplete"
            label="Expert Panels are expected to re-review all LP and VUS classifications made by the EP at least every 2 years to see if new evidence has emerged to re-classify the variants. Please provide an update on re-review activities for any LP and VUS variant classifications over 2 years" :errors="errors.rereview_lp_vus">
        </input-row>

        <input-row 
            v-model="workingCopy.data.rereview_lb"
            type="large-text"
            :disabled="isComplete"
            label="Expert Panels are expected to re-review any LB classifications when new evidence sources are available (e.g. new versions of gnomAD) or when requested by the public via the ClinGen website.  If the Expert Panel re-reviewed LB classifications this year, what was the outcome?" :errors="errors.rereview_lb">
        </input-row>
    </div>
</template>
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