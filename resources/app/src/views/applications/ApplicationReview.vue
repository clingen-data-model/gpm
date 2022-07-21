<script setup>
    import {computed, shallowRef} from 'vue';
    import {useStore} from 'vuex';
    import {hasPermission} from '@/auth_utils.js'
    import ScreenTemplate from '../../components/ScreenTemplate.vue';
    import DefinitionReview from '../../components/expert_panels/DefinitionReview.vue';
    import ChairApproverControls from '../../components/applications/Review/ChairApproverControls.vue';

    const store = useStore();
    
    const group = computed(() => store.getters['groups/currentItemOrNew'])
    const breadcrumbs = computed(() => {
        if (!group.value.uuid) {
            return [];
        }
        return [{label: group.value.displayName, route: {name: 'GroupDetail', params: {uuid: group.value.uuid}}}];
    });

    const stepReviewComponent = shallowRef(DefinitionReview)

</script>
<template>
    <ScreenTemplate :title="group.displayName" :breadcrumbs="breadcrumbs">
        <ChairApproverControls v-if="hasPermission('ep-applications-approve')" />
        <component :is="stepReviewComponent" />
    </ScreenTemplate>
</template>