<script setup>
    import {hasPermission} from '@/auth_utils.js';
    import commentManagerFactory from '@/composables/comment_manager.js'
    import {api} from '@/http';
    import {computed, onMounted, provide, ref, shallowRef, watch} from 'vue';
    import {useStore} from 'vuex';
    import ApplicationAdmin from './ApplicationAdmin.vue'
    import ApplicationReview from './ApplicationReview.vue'

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

    const getLatestSubmission = () => {
        api.get(`/api/groups/${group.value.uuid}/application/latest-submission`)
            .then(rsp => latestSubmission.value = rsp.data)
            .catch(error => {
                // eslint-disable-next-line no-console
                console.log(error)
            });
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
                await getGroup();
                commentManager.value = commentManagerFactory('App\\Modules\\Group\\Models\\Group', group.value.id)
                commentManager.value.getComments();

            }
        },
        { immediate: true }
    );


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
