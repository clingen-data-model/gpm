<script setup>
    import {computed} from 'vue';
    import {useStore} from 'vuex';
    import {hasPermission} from '@/auth_utils.js'
    import ScreenTemplate from '@/components/ScreenTemplate.vue';
    import DefinitionReview from '@/components/expert_panels/DefinitionReview.vue';
    import ChairApproverControls from '@/components/applications/Review/ChairApproverControls.vue';
    import SustainedCurationReview from '@/components/expert_panels/SustainedCurationReview.vue';

    const emits = defineEmits(['deleted', 'saved']);
    const store = useStore();
    const group = computed(() => store.getters['groups/currentItemOrNew'])
    const expertPanel = computed(() => group.value.expert_panel);

    const breadcrumbs = computed(() => {
        if (!group.value.uuid) {
            return [];
        }
        return [{label: group.value.displayName, route: {name: 'GroupDetail', params: {uuid: group.value.uuid}}}];
    });


    const stepReviewComponent = computed(() => {
        if (Number.parseInt(group.value.expert_panel.current_step) === 4) {
            return SustainedCurationReview
        }
        if (Number.parseInt(expertPanel.value.current_step) === 1) {
            return DefinitionReview
        }

        return null;
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
    <component :is="stepReviewComponent" />
  </ScreenTemplate>
</template>
