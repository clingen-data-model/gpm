<script setup>
    import {computed, ref, onMounted} from 'vue'
    import {api, queryStringFromParams} from '@/http'
    import Group from '@/domain/group'
    import AnnualUpdateAlert from '@/components/groups/AnnualUpdateAlert.vue';
    import CoiAlert from '@/components/alerts/CoiAlert.vue';
    import CocAlert from '@/components/alerts/CocAlert.vue';
    import SustainedCurationReviewAlert from '@/components/alerts/SustainedCurationReviewAlert.vue';

    const props = defineProps({
        user: {
            type: Object,
            required: true
        }
    })
    const sustainedCurationReviews = ref([]);

    const coordinatingGroups = computed(() => {
        return props.user.person.memberships
                .filter(m => m.hasPermission('annual-update-manage'))
                .map(m => m.group)
                .filter(g => g !== null)
                .map(group => new Group(group))
    });

    const getSustainedCurationReviewTasks = async () => {
        if (coordinatingGroups.value.length === 0) {
            return;
        }

        const params = {
            with: ['assignee'],
            where: {
                task_type_id: 1,
                assignee_type: 'App\\Modules\\Group\\Models\\Group',
                assignee_id: [...(new Set(coordinatingGroups.value.map(cg => cg.id)))],
                pending: 1
            }
        }

        const queryString = queryStringFromParams(params);
        const url = `/api/tasks${queryString}`;
        sustainedCurationReviews.value = await api.get(url)
                                            .then(response => {
                                                const uniqueTasks = {};
                                                response.data.forEach(task => {
                                                    uniqueTasks[task.assignee_id] = task;
                                                })
                                                return uniqueTasks;
                                            });
    }

    onMounted (() => {
        getSustainedCurationReviewTasks();
    })
</script>
<template>
  <div>
    <SustainedCurationReviewAlert
      v-for="task in sustainedCurationReviews"
      :key="task.id"
      :group="task.assignee"
      class="mb-2"
    />

    <AnnualUpdateAlert
      v-for="group in coordinatingGroups" :key="group.id"
      :group="group"
      :show-group-name="true"
      class="mb-2"
    />

    <CoiAlert
      v-for="membership in user.person.membershipsWithPendingCois"
      :key="membership.id"
      :membership="membership"
      class="mb-2"
    />

    <CocAlert :coc="user.person.coc" class="mb-2" />
  </div>
</template>
