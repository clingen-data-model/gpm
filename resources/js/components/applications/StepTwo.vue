<script>
import { useGroupsStore } from '@/stores/groups'
import BaseStep from '@/components/applications/BaseStep.vue'
import SpecificationsSection from '@/components/expert_panels/SpecificationsSection.vue'

export default {
    name: 'StepTwo',
    components: {
        BaseStep,
        SpecificationsSection
    },
    props: {

    },
    emits: ['approved', 'updated'],
    data() {
        return {

        }
    },
    setup() {
        return { groupsStore: useGroupsStore() }
    },
    computed: {
        group () {
            return this.groupsStore.currentItemOrNew
        },
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
    :step="2"
    :document-type="2"
    document-name="Draft Specifications"
    :document-gets-reviewed="false"
    approve-button-label="Approve Draft Specifications"
    title="Draft ACMG Guideline Specifications"
    @updated="$emit('updated')"
  >
    <template #sections>
      <div class="application-section screen-block">
        <h2>Draft Specifications</h2>
        <SpecificationsSection :doc-type-id="2" />
      </div>
    </template>
  </BaseStep>
</template>
