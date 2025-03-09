<script>
import mirror from '@/composables/setup_working_mirror'

export default {
    name: 'GeneCurationTotals',
    props: {
        ...mirror.props,
        errors: {
            type: Object,
            required: true
        },
    },
    emits: [  ...mirror.emits ],
    setup(props, context) {
        const {workingCopy} = mirror.setup(props, context);
        return {
            workingCopy
        }
    },
    computed: {
        lastYear () {
            return (new Date()).getFullYear()-1;
        },
        isComplete () {
            return Boolean(this.modelValue.completed_at);
        }
    }
}
</script>
<template>
  <div>
    <p>
      We will obtain information from the GCI and GeneTracker regarding completed curations, but please provide the additional information below regarding
      curation work in the last year ({{ lastYear }}). Estimates are acceptable.
    </p>
    <input-row
      v-model="workingCopy.data.in_progress_count"
      :disabled="isComplete"
      label="Curations not entered in the GCI (e.g., reviewed on calls but data not entered in the ClinGen systems)"
      type="number"
      :errors="errors.in_progress_count"
      label-width-class="w-80"
      input-class="w-16"
    />

    <input-row
      v-model="workingCopy.data.publishing_issues"
      :disabled="isComplete"
      label="Please describe if there are any issues with the publishing of curations."
      type="large-text"
      :errors="errors.publishing_issues"
      label-width-class="w-80"
      input-class="w-120"
    />
  </div>
</template>
