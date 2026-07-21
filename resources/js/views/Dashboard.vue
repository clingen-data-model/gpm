<script setup>
import { useStore } from 'vuex'
import { useRouter } from 'vue-router'
import { ref, computed, onMounted, watch } from 'vue'
import { upperCase } from 'lodash-es'
import CoiList from '@/components/people/CoiList.vue'
import PersonProfile from '@/components/people/PersonProfile.vue'
import Person from "@/domain/person"
import Group from "@/domain/group"
import configs from '@/configs'
import ApplicationActivity from '../components/dashboard/ApplicationActivity.vue';
import DashboardAlerts from '@/components/dashboard/DashboardAlerts.vue'
import NotificationList from '../components/NotificationList.vue'
import DemographicsForm from '../components/people/DemographicsForm.vue';
import CoreMemberAttestationForm from '@/components/people/CoreMemberAttestationForm.vue'
import { featureIsEnabled } from '@/utils.js'
import { hasAnyPermission, hasPermission } from '@/auth_utils.js'

const store = useStore();
const router = useRouter();
const user = computed(() => {
    return store.getters.currentUser
});
const personFromStore = computed(() => {
    return store.getters['people/currentItem'] || new Person();
})

const loadPersonInStore = () => {
    if (user.value.id && user.value.person && user.value.person.id) {
        store.commit('people/addItem', user.value.person);
        store.commit('people/setCurrentItemIndex', user.value.person);
    }
}

watch(() => user, () => {
    loadPersonInStore();
});

onMounted(() => {
    loadPersonInStore();
});

// GROUPS
// TODO: Get groups by search with TONS of info.
// TODO: Extract that work to a module.
const showPastGroups = ref(false);
const groups = computed(() => {
  return (user.value.person?.memberships || [])
    .map(membership => membership.group)
    .filter(group => group !== null)
    .map(group => new Group(group));
});
const currentGroups = computed(() => {
  const currentStatusIds = [
    configs.groups.statuses.applying.id,
    configs.groups.statuses.active.id,
  ];
  return groups.value.filter(group =>
    currentStatusIds.includes(group.status?.id)
  );
});

const pastGroups = computed(() => {
  const pastStatusIds = [
    configs.groups.statuses.retired.id,
    configs.groups.statuses.inactive.id,
  ];

  return groups.value.filter(group =>
    pastStatusIds.includes(group.status?.id)
  );
});

const groupSort = ref({
  field: 'displayName',
  desc: false,
});

const pastGroupSort = ref({
  field: 'displayName',
  desc: false,
});

const groupFields = ref([
    {
        name: 'displayName',
        sortable: true,
        type: String
    },
    {
        name: 'status.name',
        label: 'Status',
        sortable: true,
        resolveValue: (item) => {
            if (item.status.id === configs.groups.statuses.applying.id) {
                return `${item.status.name  } - ${  item.expert_panel.currentStepName}`;
            }
            return item.status.name;
        },
        type: String
    },
    {
        name: 'type.display_name',
        label: 'Type',
        sortable: true,
    }
]);

const groupBadgeColor = (status) => {
    const map = {
        active: 'green',
        applying: 'blue',
        retired: 'yellow',
        inactive: 'gray',
        removed: 'red',
    };
    return map[status?.toLowerCase()] || 'blue';
};

const showApplicationActivity = computed(() => {
    return hasPermission('ep-applications-manage')
        || (
            featureIsEnabled('chair_review')
            && hasAnyPermission(['ep-applications-comment', 'ep-applications-approve'])
        )
})

onMounted(async () => {
    await store.dispatch('getCurrentUser');
})

const navigateToGroup = (item) => {
    router.push({
        name: 'GroupDetail',
        params: { uuid: item.uuid }
    })
}
</script>
<template>
  <div>
    <h1>
      Dashboard
      <div class="note font-normal">
        User ID: {{ user.id }} | Person ID: {{ user.person ? user.person.id : 'no person!!' }}
      </div>
    </h1>

    <NotificationList :user="user" />

    <DashboardAlerts :user="user" />

    <ApplicationActivity v-if="showApplicationActivity" :user="user" class="screen-block" />

    <tabs-container class="mt-8">
      <tab-item label="Your Groups">
        <div v-if="!groups.length" class="well">You are not assigned to any groups.</div>
        <template v-else>
          <div v-if="!currentGroups.length" class="well">You are not assigned to any active or applying groups.</div>
          <data-table v-else v-model:sort="groupSort" :data="currentGroups" :fields="groupFields" row-class="cursor-pointer" @row-click="navigateToGroup">
            <template #cell-status_name="{ value }">
              <badge :color="groupBadgeColor(value)">{{ value }}</badge>
            </template>
            <template #cell-displayName="{ item }">
              {{ item.name }} {{ item.type.display_name }}
            </template>
          </data-table>

          <div v-if="pastGroups.length" class="mt-4">
            <label class="flex items-center gap-2 cursor-pointer">
              <input v-model="showPastGroups" type="checkbox">
              <span>Show inactive and retired groups ({{ pastGroups.length }})</span>
            </label>
          </div>

          <div v-if="showPastGroups && pastGroups.length" class="mt-4">
            <h3>Inactive and retired groups</h3>
            <p class="text-sm text-gray-600">These groups are no longer active but remain available for reference.</p>

            <data-table
              v-model:sort="pastGroupSort"
              :data="pastGroups"
              :fields="groupFields"
              row-class="cursor-pointer"
              @row-click="navigateToGroup"
            >
              <template #cell-status_name="{ value }"><badge :color="groupBadgeColor(value)">{{ value }}</badge></template>
              <template #cell-displayName="{ item }">{{ item.name }} {{ item.type.display_name }}</template>
            </data-table>
          </div>
        </template>
      </tab-item>

      <tab-item label="Your Info">
        <PersonProfile :person="personFromStore" />
      </tab-item>

      <tab-item label="COIs">
        <CoiList :person="user.person" />
      </tab-item>

      <tab-item label="Demographics">
        <DemographicsForm :uuid="user.person.uuid" />
      </tab-item>
      
      <tab-item v-if="user.person.has_core_member_attestation" label="Attestation">
        <CoreMemberAttestationForm :person-uuid="user.person.uuid" :editing="user.person.requires_core_member_attestation" />
      </tab-item>
    </tabs-container>
  </div>
</template>
