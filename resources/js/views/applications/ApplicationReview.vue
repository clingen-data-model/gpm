<script setup>
import { computed, inject } from 'vue'
import {useStore} from 'vuex';
import {hasPermission} from '@/auth_utils.js'
import ScreenTemplate from '@/components/ScreenTemplate.vue';
import DefinitionReview from '@/components/expert_panels/DefinitionReview.vue';
import ChairApproverControls from '@/components/applications/Review/ChairApproverControls.vue';
import SustainedCurationReview from '@/components/expert_panels/SustainedCurationReview.vue';
import SubmissionContextSummary from '@/components/applications/Review/SubmissionContextSummary.vue';

const emits = defineEmits(['deleted', 'saved']);
const store = useStore();
const group = computed(() => store.getters['groups/currentItemOrNew'])
const latestSubmission = inject('latestSubmission')

const breadcrumbs = computed(() => {
  if (!group.value.uuid) {
    return [];
  }
  return [{label: group.value.displayName, route: {name: 'GroupDetail', params: {uuid: group.value.uuid}}}];
});

const reviewStep = computed(() => {
  const submission = latestSubmission.value
  // SoW revisions are handled in the same step as the initial SoW review, so we need to check for that context and use the approval_step value if present. 
  // Otherwise, we just use the current_step value from the expert panel.
  if (submission?.data?.context === 'scope_of_work_revision') {
    return Number.parseInt(submission.data?.approval_step ?? 1)
  }
  return Number.parseInt(group.value?.expert_panel?.current_step)
})

const stepReviewComponent = computed(() => {
  if (reviewStep.value === 1) {
    return DefinitionReview
  }

  if (reviewStep.value === 4) {
    return SustainedCurationReview
  }
  return null
})

const screenTitle = computed(() => {
  if (group.value.is_gcep) {
    return group.value.displayName;
  }

  return `${group.value.displayName} - ${group.value.expert_panel.currentStepName}`
})

</script>
<template>
  <ScreenTemplate :title="screenTitle" :breadcrumbs="breadcrumbs">
    <ChairApproverControls
      v-if="hasPermission('ep-applications-approve')"
      @deleted="emits('deleted')"
      @saved="emits('deleted')"
    />
    <SubmissionContextSummary @saved="emits('saved')" />
    <component :is="stepReviewComponent" v-if="stepReviewComponent" />
  </ScreenTemplate>
</template>
