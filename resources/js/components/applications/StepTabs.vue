<script>
import { mapGetters } from 'vuex'
import StepOne from '@/components/applications/StepOne.vue'
import StepTwo from '@/components/applications/StepTwo.vue'
import StepThree from '@/components/applications/StepThree.vue'
import StepFour from '@/components/applications/StepFour.vue'

export default {
    components: {
        StepOne,
        StepTwo,
        StepThree,
        StepFour,
    },
    emits: ['approved', 'updated'],
    data() {
        return {
            activeStep: 1
        }
    },
    computed: {
        ...mapGetters({
            group: 'groups/currentItemOrNew'
        }),
        application () {
            return this.group.expert_panel;
        },
        activeIndex: {
            deep: true,
            get() {
                return (this.application && this.application.current_step) ? this.application.current_step - 1 : 0
            },
            set(value) {
                this.activeStep = value+1
            }

        }
    },
    methods: {
        handleApproved () {
            this.$emit('approved');
            this.$emit('updated');
        },
        handleUpdated () {
            this.$emit('updated')
        }
    }
}
</script>
<template>
  <div>
    <div v-if="application.is_gcep">
      <StepOne @step-approved="handleApproved" @updated="handleUpdated" />
    </div>
    <tabs-container
      v-if="application.expert_panel_type_id == 2"
      v-model="activeIndex"
      tab-location="right"
    >
      <tab-item label="Group Definition">
        <StepOne @step-approved="handleApproved" @updated="handleUpdated" />
      </tab-item>
      <tab-item label="Draft Specifications">
        <StepTwo @step-approved="handleApproved" @updated="handleUpdated" />
      </tab-item>
      <tab-item label="Pilot Specfications">
        <StepThree @step-approved="handleApproved" @updated="handleUpdated" />
      </tab-item>
      <tab-item label="Sustained Curation">
        <StepFour @step-approved="handleApproved" @updated="handleUpdated" />
      </tab-item>
    </tabs-container>
  </div>
</template>
