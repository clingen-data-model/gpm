<script>
import mirror from '@/composables/setup_working_mirror'

export default {
    name: 'SpecificationProgress',
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
  <div>
    <input-row
      v-model="workingCopy.data.specifications_for_new_gene"
      label="Are you planning to start the rule specification process for a gene within your scope in the coming year?"
      type="radio-group"
      :errors="errors.specifications_for_new_gene"
      vertical
      :options="[{value: 'yes'}, {value: 'no'}]"
      :disabled="isComplete"
    />

    <transition name="slide-fade-down">
      <input-row
        v-if="workingCopy.data.specifications_for_new_gene === 'yes'"
        v-model="workingCopy.data.specifications_for_new_gene_details"
        label="What are your plans?"
        :errors="errors.specifications_for_new_gene_details"
        type="large-text"
        vertical
        :disabled="isComplete"
      />
    </transition>

    <input-row
      v-model="workingCopy.data.submit_clinvar"
      label="Have you submitted any variant to ClinVar over the last year (Jan 1 to Dec 31 of the prior year)?"
      type="radio-group"
      :errors="errors.submit_clinvar"
      vertical
      :options="[{value: 'yes'}, {value: 'no'}]"
      :disabled="isComplete"
    />

    <transition name="slide-fade-down">
      <input-row
        v-if="workingCopy.data.submit_clinvar === 'no'"
        v-model="workingCopy.data.vcep_publishing_issues"
        label="Please indicate issues and/or concerns with publishing."
        :errors="errors.submit_clinvar"
        type="large-text"
        vertical
        :disabled="isComplete"
      />
    </transition>

    <input-row

      v-model="workingCopy.data.system_discrepancies"
      label="Are there any discrepancies between the number of variants published to ClinVar and those within the ClinGen systems (e.g., ClinGen Evidence Repository [Erepo])? Please indicate your plans for getting variants published, and/or describe issues or challenges to publishing these curations."
      :errors="errors.system_discrepancies"
      type="large-text"
      vertical
      :disabled="isComplete"
    />
  </div>
</template>
