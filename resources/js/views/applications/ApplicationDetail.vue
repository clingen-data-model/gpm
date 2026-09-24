<script setup>
    import {computed, onUnmounted, onMounted, provide, ref, shallowRef, watch} from 'vue';
    import {useStore} from 'vuex';
    import {hasPermission} from '@/auth_utils.js';
    import ApplicationAdmin from './ApplicationAdmin.vue'
    import ApplicationReview from './ApplicationReview.vue'
    import commentManagerFactory from '@/composables/comment_manager.js'
    import {api} from '@/http';
    import { useScopeOfWorkComparison } from '@/composables/scope_of_work_comparison';

    const props = defineProps({
        uuid: {
            type: String,
            requird: true
        }
    })
    const commentManager = ref(commentManagerFactory('App\\Modules\\Group\\Models\\Group', 0));
    provide('commentManager', commentManager)

    const store = useStore();

    const loading = ref(false);
    const group = computed(() => store.getters['groups/currentItemOrNew'])
    provide('group', group);

    const applicationView = shallowRef(ApplicationReview);
    const latestSubmission = ref({});
    provide('latestSubmission', latestSubmission);
    const reviewHistory = ref(null);
    const reviewHistoryLoading = ref(false);
    const reviewHistoryError = ref('');
    provide('applicationReviewHistory', { history: reviewHistory, loading: reviewHistoryLoading, error: reviewHistoryError });
    let reviewRefresh = 0;
    const scopeOfWorkComparisonState = useScopeOfWorkComparison(() => [
        group.value.uuid,
        latestSubmission.value?.data?.context === 'scope_of_work_revision'
            ? latestSubmission.value.id : null,
    ]);
    provide('scopeOfWorkComparisonState', scopeOfWorkComparisonState);

    const getLatestSubmission = async () => {
        const refresh = ++reviewRefresh;
        const uuid = group.value.uuid;
        reviewHistory.value = null;
        reviewHistoryLoading.value = true;
        reviewHistoryError.value = '';
        await Promise.allSettled([
            api.get(`/api/groups/${uuid}/application/latest-submission`).then(rsp => {
                if (refresh === reviewRefresh) latestSubmission.value = rsp.data;
            }),
            api.get(`/api/groups/${uuid}/application/review-history`).then(rsp => {
                if (refresh === reviewRefresh) reviewHistory.value = rsp.data;
            }).catch(() => {
                if (refresh === reviewRefresh) reviewHistoryError.value = 'Unable to load Review History. Refresh to retry.';
            }),
        ]);
        if (refresh === reviewRefresh) reviewHistoryLoading.value = false;
    }
    const getGroup = async () => {
        loading.value = true;
        await store.dispatch('groups/findAndSetCurrent', props.uuid);
        store.dispatch('groups/getDocuments', group.value);
        store.dispatch('groups/getNextActions', group.value);
        store.dispatch('groups/getSubmissions', group.value);
        getLatestSubmission();
        store.dispatch('groups/getMembers', group.value);
        store.dispatch('groups/getGenes', group.value);
        loading.value = false;
    };

    watch(
        () => props.uuid,
        async (to, from) => {
            if ((to && (!from || to !== from))) {
                reviewRefresh++;
                reviewHistory.value = null;
                latestSubmission.value = {};
                await getGroup();
                commentManager.value.dispose();
                commentManager.value = commentManagerFactory('App\\Modules\\Group\\Models\\Group', group.value.id, group.value.uuid, group.value.id)
                commentManager.value.getSummary();
                commentManager.value.getComments();

            }
        },
        { immediate: true }
    );


    onUnmounted(() => commentManager.value.dispose());

    onMounted(async () => {
        if (hasPermission('ep-applications-comment')) {
            applicationView.value = ApplicationReview
        }
        if (hasPermission('ep-applications-manage')) {
            applicationView.value = ApplicationAdmin
        }
    })
</script>
<template>
  <component
    :is="applicationView"
    :loading="loading"
    @updated="getGroup"
    @saved="getLatestSubmission"
    @deleted="getLatestSubmission"
  />
  <div v-show="loading">
    Loading&hellip;
  </div>
</template>
