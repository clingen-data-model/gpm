<script>
import ApplicationSection from '@/components/expert_panels/ApplicationSection.vue'
import mirror from '@/composables/setup_working_mirror'

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
    setup(props, context) {
        const {workingCopy} = mirror.setup(props, context);
        return {
            workingCopy
        }
    },
    computed: {
        isComplete () {
            return Boolean(this.modelValue.completed_at)
        }
    }
}
</script>
<template>
  <ApplicationSection title="Variant Re-review">
    <p>
      Variant Curation Expert Panels are expected to keep their variant interpretations up-to-date and to expedite the re-review of variants that have a conflicting assertion submitted to ClinVar after the Expert Panel submission (guidelines for recuration timeline provided in the <a href="https://clinicalgenome.org/docs/clingen-variant-curation-expert-panel-vcep-protocol/">VCEP protocol</a>).  <br>
      <br>
      Please answer the following question concerning recuration:
    </p>

    <ul class="list-decimal px-6">
      <li>
        <input-row v-model="workingCopy.data.rereview_discrepencies_progress" :disabled="isComplete" vertical type="large-text" :errors="errors.rereview_discrepencies_progress" placeholder="Progress...">
          <template #label>
            Are you receiving and/or using the VCEP Variant Tracker reports to aid in the recuration work? Please describe below.
          </template>
        </input-row>
      </li>
    </ul>
  </ApplicationSection>
</template>