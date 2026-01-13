<script setup>
import { computed } from 'vue'
import { useStore } from 'vuex'
import { api } from '@/http'

const store = useStore()

const memberships = computed(() =>
  store.getters.currentUser?.person?.membershipsWithPendingCois || []
)

const env = computed(() => store.state.systemInfo?.env)
const isNonProd = computed(() => env.value && (env.value == 'demo' || env.value == 'local'))

const isSuper = computed(() => {
  const roles = store.getters.currentUser?.roles || []
  return roles.some(r => ['super-user', 'super-admin'].includes(r.name))
})

const canAdminComplete = computed(() => isNonProd.value && isSuper.value)

async function adminCompleteCoi (membership) {
  await api.post(`/api/coi/${membership.group.coi_code}`, {
    group_member_id: membership.id,
    work_fee_lab: 0,
    contributions_to_gd_in_group: 2,
    coi: 0,
    coi_attestation: 1,
    data_policy_attestation: 1,
    admin_override: 1,
  })
  await store.dispatch('forceGetCurrentUser')
}
</script>

<template>
  <card title="You have Conflict of Interest Disclosures to Complete">
    <h3>You must complete a Conflict of Interest Disclosure for the following memberships:</h3>
    <div class="my-2">
      <div
        v-for="membership in memberships"
        :key="membership.id"
        class="flex items-center justify-between my-0 p-2 border border-gray-300 first:rounded-t-lg last:rounded-b-lg hover:bg-blue-50"
      >
        <router-link
          class="font-bold link"
          :to="`/coi/${membership.group.coi_code}`"
        >
          {{ membership.group.display_name }}
        </router-link>

        <div class="flex items-center space-x-2">
          <button v-if="canAdminComplete" class="btn btn-xs" @click.stop="adminCompleteCoi(membership)">Complete COI</button>
        </div>
      </div>
    </div>
  </card>
</template>
