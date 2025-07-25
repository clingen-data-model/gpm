<script setup>
    import {judgementColor} from '@/composables/judgement_utils.js'
    import {ref, onMounted} from 'vue'
    import {useRouter} from 'vue-router'
    import {api} from '@/http'
    import {hasPermission} from '@/auth_utils';
    import { featureIsEnabled } from '@/utils.js';

    const props = defineProps({
        user: {
            type: Object,
            required: true
        }
    });

    const router = useRouter();

    const fields = [
        {
            name: 'name',
            label: 'Group',
            sortable: true
        },
        {
            name: 'submission.type.name',
            label: 'Submitted Step',
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
            && group.submission.judgements.some(j => Number.parseInt(j.person_id) === Number.parseInt(props.user.person.id));
    }

    const judgementFor = group => {
        if (!group.submission.judgements) return {};

        return group.submission.judgements.find(j => Number.parseInt(j.person_id) === Number.parseInt(props.user.person.id));
    }

    onMounted(() => {
        getApplicationActivity();
    })

</script>
<template>
  <div>
    <h2>Application activity</h2>
    <div v-if="groups.length == 0" class="alert bg-gray-100 p-2 text-gray-400">
      No application activity.
    </div>
    <data-table
      v-else
      v-model:sort="sort"
      :data="groups"
      :fields="fields"
      class="text-sm"
      row-class="cursor-pointer"
      @row-click="goToApplication"
    >
      <template #cell-actions="{item}">
        <div class="flex space-x-2">
          <div v-if="hasMadeJudgementOn(item)">
            <popper hover arrow placement="right">
              <badge :color="judgementColor(item)">
                <icon-checkmark class="text-white inline-block" width="12" height="12" />
              </badge>

              <template #content>
                <div>
                  <h3 class="mb-2">
                    Your Decision
                  </h3>
                  <badge class="inline-block" :color="judgementColor(judgementFor(item))" size="xs">
                    {{ judgementFor(item).decision }}
                  </badge>
                </div>
                <div v-if="judgementFor(item).notes" class="mt-2" style="max-width: 300px">
                  <strong>Notes:</strong> {{ judgementFor(item).notes }}
                </div>
                <hr>
                <router-link
                  :to="{name: 'ApplicationDetail', params: {uuid: item.uuid}}"
                  :class="{'btn btn-xs': hasPermission('ep-applications-approve')}"
                >
                  {{ 'Review' }}
                </router-link>
              </template>
            </popper>
          </div>

          <router-link
            v-else
            :to="{name: 'ApplicationDetail', params: {uuid: item.uuid}}"
            class="btn btn-xs"
          >
            {{ hasPermission('ep-applications-manage') ? 'View' : 'Review' }}
          </router-link>

          <div>
            <popper v-if="featureIsEnabled('chair-review')" hover arrow>
              <badge v-if="hasPermission('ep-applications-manage') && item.submission.status.name == 'Under Chair Review'" :color="item.submission.judgements.length == 3 ? 'green' : 'gray' ">
                {{ item.submission.judgements.length }}/3
              </badge>

              <template #content>
                <h3>Decisions:</h3>
                <table style="max-width: 350px" class="text-sm">
                  <tr v-for="j in item.submission.judgements" :key="j.id">
                    <th>{{ j.person.name }}:</th>
                    <td>
                      <badge class="inline-block" :color="judgementColor(j)">
                        {{ j.decision }}
                      </badge>
                    </td>
                  </tr>
                </table>
              </template>
            </popper>
          </div>
        </div>
      </template>
    </data-table>
  </div>
</template>

<style scoped>
    table {
        @apply border-none;
    }
    table td, table th {
        @apply border-none text-left pl-0;
    }
</style>
