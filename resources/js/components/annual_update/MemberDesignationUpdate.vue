<script>
import MemberDesignationForm from '@/components/expert_panels/MemberDesignationForm.vue'
import mirror from '@/composables/setup_working_mirror'
import ApplicationSection from '@/components/expert_panels/ApplicationSection.vue'

export default {
    name: 'MemberDesignationUpdate',
    components: {
        MemberDesignationForm,
        ApplicationSection
    },
    props: {
        ...mirror.props,
        errors: {
            type: Object,
            required: true
        },
        version: {
            type: Number,
            required: false,
            default: 0,
        },
    },
    emits: [ ...mirror.emits, 'updated' ],
    setup(props, context) {
        const {workingCopy} = mirror.setup(props, context);
        return {
            workingCopy
        }
    },
    computed: {
        isComplete () {
            return Boolean(this.modelValue.completed_at);
        }
    },
    methods: {
        save () {
            this.$refs.memberDesignationForm.save();
        }
    }
}
</script>
<template>
  <ApplicationSection title="Member Designation">
    <MemberDesignationForm
      ref="memberDesignationForm"
      v-model="workingCopy"
      :errors="errors"
      :readonly="isComplete"
      @updated="$emit('updated')"
    />
    <hr>
    <input-row
      v-if="version < 2024"
      v-model="workingCopy.data.member_designation_changed"
      vertical
      label="Does this represent a change from previous years?"
      :errors="errors.member_designation_changed"
      type="radio-group"
      :options="[
        {value: 'yes', label: 'Yes'},
        {value: 'no', label: 'No'}
      ]"
      :disabled="isComplete"
    />
  </ApplicationSection>
</template>
