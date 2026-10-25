<script>
import { inject, ref } from 'vue'
import { activeScopeOfWorkReviewSubmission, initialReviewStep } from '@/domain/review_submission'
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
    setup() { return { selectReviewStep: inject('selectReviewStep', () => {}), latestSubmission: inject('latestSubmission', ref(null)) } },
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
        revisionSubmission() { return activeScopeOfWorkReviewSubmission(this.group, this.latestSubmission); },
        initialStep() { return initialReviewStep(this.group, this.latestSubmission); },
        initializationKey() { return `${this.group.uuid}:${this.revisionSubmission?.id ?? 'initial'}:${this.initialStep}`; },
        activeIndex: {
            deep: true,
            get() {
                return this.activeStep - 1
            },
            set(value) {
                this.activeStep = value+1
            }

        }
    },
    watch: {
        activeStep: { immediate: true, handler(step) { this.selectReviewStep(step); } },
        initializationKey: {
            immediate: true,
            handler () { this.activeStep = this.initialStep; }
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
      v-if="application.expert_panel_type_id == 2 || application.expert_panel_type_id == 3"
      v-model="activeIndex"
      :sync-route="!revisionSubmission"
      tab-location="right"
    >
      <tab-item label="Group Definition">
        <StepOne @step-approved="handleApproved" @updated="handleUpdated" />
      </tab-item>
      <tab-item label="Draft Specifications">
        <StepTwo @step-approved="handleApproved" @updated="handleUpdated" />
      </tab-item>
      <tab-item label="Pilot Specifications">
        <StepThree @step-approved="handleApproved" @updated="handleUpdated" />
      </tab-item>
      <tab-item label="Sustained Curation">
        <StepFour @step-approved="handleApproved" @updated="handleUpdated" />
      </tab-item>
    </tabs-container>
  </div>
</template>
