<script setup>
    import {ref, computed, onMounted} from 'vue'
    import {useRouter} from 'vue-router'
    import {api} from '@/http'

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
    ]

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
                <router-link :to="{name: 'ApplicationDetail', params: {uuid: item.uuid}}">
                    {{hasPermission('ep-applications-manage') ? 'View' : 'Review'}}
                </router-link>
            </template>
        </data-table>
    </div>        
</template>