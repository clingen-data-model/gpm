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
    <p>Gene Curation Expert Panels are expected to review current clinical validity classifications for their approved genes based on the guidance provided in the <gcep-recuration-process-link />. Please indicate the following:</p>

    <input-row
      v-model="workingCopy.data.recuration_begun"
      :disabled="isComplete"
      label="Have you begun recuration?"
      type="radio-group"
      :options="[{value:'yes'},{value:'no'}]"
      :errors="errors.recuration_begun"
      vertical
    />

    <input-row
      v-model="workingCopy.data.recuration_designees"
      :disabled="isComplete"
      :errors="errors.recuration_designees"
      type="large-text"
      placeholder="First Last, Email"
      vertical
    >
      <template #label>
        The GCEP recuration designee(s) is designated to monitor for recuration updates, on a yearly basis, according to the <gcep-recuration-process-link />. Please list the name(s) of your GCEP recuration designee(s).
      </template>
    </input-row>
  </div>
</template>
