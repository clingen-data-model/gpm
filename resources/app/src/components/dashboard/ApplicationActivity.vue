<script setup>
    import {ref, computed, onMounted} from 'vue'
    import {useRouter} from 'vue-router'
    import {api} from '@/http'
import {hasPermission} from '../../auth_utils';

    const router = useRouter();

    const props = defineProps({
        user: {
            type: Object,
            required: true
        }
    });

    const fields = [
        {
            name: 'name',
            label: 'Group',
            sortable: true
        },
        {
            name: 'submission.type.name',
            label: 'Submissed Step',
            sortable: true
        },
        {
            name: 'submission.status.name',
            label: 'Status',
            sortable: true
        },
        {
            name: 'last_submission_date',
            label: 'Date',
            sortable: true,
            type: Date
        },
        {
            name: 'actions',
            label: '',
            sortable: false
        }
    ];

    const sort = ref({field: 'name', desc: false})

    const groups = ref([]);
    const getApplicationActivity = async () => {
        groups.value = await api.get('/api/groups/applications').then(response => response.data.data)
    }

    const goToApplication = (group) => {
        router.push({
            name: 'ApplicationDetail', 
            params:{
                uuid: group.uuid
            }
        });
    }

    const hasMadeJudgementOn = group => {
        return group.submission.judgements
            && group.submission.judgements.some(j => j.person_id == props.user.person.id);
    }

    const judgementFor = group => {
        if (!group.submission.judgements) return {};
        
        return group.submission.judgements.find(j => j.person_id == props.user.person.id);
    }

    const judgementColor = group => {
        if (!group.submission.judgements) return 'gray';
        const decToColor = {'approved': 'green', 'approve-after-revisions': 'blue', 'request-revisions': 'yellow'}
        return decToColor[judgementFor(group).decision];
    }

    onMounted(() => {
        getApplicationActivity();
    })

</script>
<template>
    <div>
        <h2>Application activity</h2>
        <!-- <pre>{{groups}}</pre> -->
        <data-table 
            :data="groups" 
            :fields="fields" 
            v-model:sort="sort" 
            class="text-sm"
            @rowClick="goToApplication"
            row-class="cursor-pointer"
        >
            <template v-slot:cell-actions="{item}">
                <div class="flex space-x-2">
                    <div v-if="hasMadeJudgementOn(item)">
                        <popper hover arrow placement="right">
                            <badge :color="judgementColor(item)">
                                <icon-checkmark class="text-white inline-block" width="12" height="12"></icon-checkmark>
                            </badge>

                            <template v-slot:content>
                                <div>
                                    <h3 class="mb-2">Your Decision</h3>
                                    <badge class="inline-block" :color="judgementColor(item)" size="xs">
                                        {{judgementFor(item).decision}}
                                    </badge>
                                </div>
                                <div v-if="judgementFor(item).notes" class="mt-2" style="max-width: 300px">
                                    <strong>Notes:</strong> {{judgementFor(item).notes}}
                                </div>
                                <hr>
                                <router-link
                                    :to="{name: 'ApplicationDetail', params: {uuid: item.uuid}}" 
                                    :class="{'btn btn-xs': hasPermission('ep-applications-approve')}"
                                >
                                    {{'Review'}}
                                </router-link>

                            </template>
                        </popper>
                    </div>
                    <router-link v-else
                        :to="{name: 'ApplicationDetail', params: {uuid: item.uuid}}" 
                        class="btn btn-xs"
                    >
                        {{hasPermission('ep-applications-manage') ? 'View' : 'Review'}}
                    </router-link>
                </div>
            </template>
        </data-table>
    </div>        
</template>