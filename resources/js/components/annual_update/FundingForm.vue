<script>
// NOTE: this is obsolete as of the 2024 annual update!
// keeping it in for now to be able to view old data

import mirror from '@/composables/setup_working_mirror'

export default {
    name: 'FundingForm',
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
    data() {
        return {
            fundingOptions: [
                {value: 'NIH U24', label: 'NIH U24'},
                {value: 'Professional society'},
                {value: 'Patient Advocacy Group'},
                {value: 'Pharma'},
                {value: 'Industry'},
                {value: 'Other'},
            ]
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
  <div>
    <input-row
      v-model="workingCopy.data.applied_for_funding"
      :disabled="isComplete"
      type="radio-group"
      :options="[{value: 'yes'}, {value: 'no'}]"
      label="Have you applied for external funding to directly support the work of your EP (e.g. NIH U24, industry, etc.)?"
      :errors="errors.applied_for_funding"
      vertical
    />

    <input-row
      v-if="workingCopy.data.applied_for_funding == 'yes'"
      v-model="workingCopy.data.funding"
      :disabled="isComplete" label="Funding Type"
      :errors="errors.funding"
      class="ml-4"
      type="select"
      :options="fundingOptions"
    />

    <input-row
      v-if="workingCopy.data.funding == 'Other'"
      v-model="workingCopy.data.funding_other_details"
      :disabled="isComplete"
      type="large-text"
      :errors="errors.funding_other_details"
      placeholder="details"
      class="ml-8"
      vertical
    />

    <input-row
      v-if="workingCopy.data.applied_for_funding == 'no'"
      v-model="workingCopy.data.funding_thoughts"
      :disabled="isComplete"
      type="large-text"
      label="Please describe any thoughts, ideas, or plans for soliciting funding or personnel (in addition to any existing funding/support you may have)."
      :errors="errors.funding_thoughts"
      vertical
      class="ml-4"
    />
  </div>
</template>
