<template>
    <application-section title="Variant Re-review">
        <p>
            Variant Curation Expert Panels are expected to keep their variant interpretations up-to-date and to expedite the re-review of variants that have a conflicting assertion submitted to ClinVar after the Expert Panel submission. Please describe progress in each of the following activities and describe any reasons expectations have not been met.
        </p>

        <ul class="list-decimal px-6">
            <li>
                <input-row :disabled="isComplete" vertical type="large-text" :errors="errors.rereview_discrepencies_progress" v-model="workingCopy.rereview_discrepencies_progress" placeholder="Progress...">
                    <template v-slot:label>
                        Discrepancies with Expert Panel classifications from a newly submitted ClinVar entry from a one star submitter or above (expected to be addressed within 6 months of posting).
                    </template>
                </input-row>
            </li>
            <li>
                <input-row :disabled="isComplete" vertical type="large-text" :errors="errors.rereview_lp_and_vus_progress" v-model="workingCopy.rereview_lp_and_vus_progress" placeholder="Progress...">
                    <template v-slot:label>
                        Expert Panels are expected to re-review all LP and VUS classifications made by the EP at least every 2 years to see if new evidence has emerged to re-classify the variants. Please provide an update on re-review activities for any LP and VUS variant classifications over 2 years.
                    </template>
                </input-row>
            </li>
            <li>
                <input-row :disabled="isComplete" vertical type="large-text" :errors="errors.rereview_lb_progress" v-model="workingCopy.rereview_lb_progress" placeholder="Progress...">
                    <template v-slot:label>
                        Expert Panels are expected to re-review any LB classifications when new evidence sources are available (e.g. new versions of gnomAD) or when requested by the public via the ClinGen website. If the Expert Panel re-reviewed LB classifications this year, what was the outcome?
                    </template>
                </input-row>
            </li>
        </ul>
    </application-section>
</template>
<script>
import mirror from '@/composables/setup_working_mirror'
import ApplicationSection from '@/components/expert_panels/ApplicationSection'

export default {
    name: 'VariantReanalysis',
    components: {
        ApplicationSection
    },
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