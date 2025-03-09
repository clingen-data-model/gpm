<script>
import {mapGetters} from 'vuex'
import BaseStep from '@/components/applications/BaseStep.vue'
// import EvidenceSummaryList from '@/components/expert_panels/EvidenceSummaryList.vue'
// import VcepOngoingPlansForm from '@/components/expert_panels/VcepOngoingPlansForm.vue'
// import MemberDesignationForm from '@/components/expert_panels/MemberDesignationForm.vue';
import SustainedCurationReview from '@/components/expert_panels/SustainedCurationReview.vue'

export default {
    name: 'StepFour',
    components: {
        BaseStep,
        // EvidenceSummaryList,
        // VcepOngoingPlansForm,
        // MemberDesignationForm,
        SustainedCurationReview
    },
    props: {

    },
    emits: ['approved', 'updated'],
    data() {
        return {

        }
    },
    computed: {
        ...mapGetters({
            group: 'groups/currentItemOrNew'
        }),
        application () {
            return this.group.expert_panel;
        },
    },
    methods: {

    }
}
</script>
<template>
  <BaseStep
    :step="4"
    :document-type="5"
    document-name="Sustained Curation"
    :document-gets-reviewed="true"
    approve-button-label="Approve Sustained Curation"
    title="Sustained Curation"
    @updated="$emit('updated')"
  >
    <template #sections>
      <SustainedCurationReview />
    </template>
  </BaseStep>
</template>
