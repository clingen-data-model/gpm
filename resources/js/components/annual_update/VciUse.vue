<script>
import mirror from '@/composables/setup_working_mirror'

export default {
    name: 'VciUse',
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
      v-model="workingCopy.data.vci_use"
      type="radio-group"
      :options="[{value: 'yes'},{value: 'no'}]"
      label="Please Indicate whether the Expert Panel uses the VCI for all variant curation activities, or if the Expert Panel intends to use the VCI once they begin curation. If not used for all activities, please describe."
      :errors="errors.vci_use"
      vertical
      :disabled="isComplete"
    />
    <transition name="slide-fade-down">            
      <input-row 
        v-if="workingCopy.data.vci_use == 'no'" 
        v-model="workingCopy.data.vci_use_details"
        type="large-text"
        :errors="errors.vci_use_details" 
        label="Please explain" 
        vertical 
        class="ml-4"
        :disabled="isComplete"
      />
    </transition>
  </div>
</template>