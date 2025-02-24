<script>
import BaseStep from '@/components/applications/BaseStep.vue'
import DefinitionReview from '@/components/expert_panels/DefinitionReview.vue'
import {mapGetters} from 'vuex'

export default {
    name: 'StepOne',
    components: {
        BaseStep,
        DefinitionReview
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
  <div class="application-review">
    <BaseStep 
      :step="1" 
      document-name="Scope and Membership Application" 
      title="Scope and Membership" 
      :document-type="1" 
      :document-gets-reviewed="true"
      approve-button-label="Approve Scope and Membership"
      @updated="$emit('updated')"
    >
      <template #sections>
        <DefinitionReview />
      </template>
    </BaseStep>
  </div>
</template>