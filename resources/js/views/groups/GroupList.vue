<script>
import {useStore} from 'vuex'
import {useRouter} from 'vue-router'
import {computed, ref} from 'vue'
import GroupForm from '@/components/groups/GroupForm.vue'
import SubmissionWrapper from '@/components/groups/SubmissionWrapper.vue'

export default {
    name: 'ComponentName',
    components: {
        GroupForm,
        SubmissionWrapper
    },
    props: {

    },
    setup() {
        const store = useStore();
        const router = useRouter();


        const tabDefinitions = computed( () => {
            const tabs = [
                {
                    label: 'VCEPs',
                    typeId: 4,
                    filter: (g) => g.is_vcep_or_scvcep,
                },
                {
                    label: 'GCEPs',
                    typeId: 3,
                    filter: (g) => g.is_gcep,
                },
{
                    label: 'CDWGs',
                    typeId: 2,
                    filter: g => g.isCdwg()
                },
                {
                    label: 'WGs',
                    typeId: 1,
                    filter: g => g.isWg()
                },
            ];

            return tabs;
        })



        const filterString = ref(null);

        const groups = computed(() => store.getters['groups/all']);

        const filteredGroups = computed(() => groups.value.filter(group => {
            if (!filterString.value) {
                return true;
            }

            const pattern = new RegExp(`.*${filterString.value}.*`, 'i');

            return group.name && group.name.match(pattern)
                || group.displayName && group.displayName.match(pattern)
                || (
                    group.expert_panel &&
                    group.expert_panel.full_short_base_name &&
                    group.expert_panel.full_short_base_name.match(pattern)
                )
                || Number.parseInt(group.id) === Number.parseInt(filterString.value)
                || group.status.name.match(pattern)
                || (
                    group.expert_panel &&
                    group.expert_panel.currentStepName &&
                    group.expert_panel.currentStepName.match(pattern)
                    )
                || group.coordinators.filter(c => c.person.name.match(pattern)).length > 0
        }))

        const goToItem = (item) => {
            router.push({
                name: 'GroupDetail',
                params: {uuid: item.uuid}
            })
        }

        return {
            filterString,
            groups,
            filteredGroups,
            tabDefinitions,
            goToItem,
            goToGroup: goToItem,
        }
    },
    data() {
        return {
            loading: false,
            showCreateForm: false,
            loadedFor: {
                'VCEPs': false,
                'GCEPs': false,
                'CDWGs': false,
                'WGs': false,
            },
            sort: {
                field: 'id',
                desc: false
            },
            fields: [
                {
                    name: 'id',
                    label: 'ID',
                    sortable: true
                },
                {
                    name: 'name',
                    label: 'Name',
                    sortable: true,
                    resolveValue: (item) => {
                        return item.displayName
                    }
                },
                {
                    name: 'coordinators',
                    sortable: false
                },
                {
                    name: 'displayStatus',
                    sortable: true,
                    label: 'status'
                },
            ]
        }
    },
    methods: {
        startCreateGroup () {
            this.showCreateForm = true;
        },
        async getGroupsForType (tabLabel) {
            const typeTab = this.tabDefinitions.find(t => t.label === tabLabel);
            if (this.loadedFor[tabLabel]) {
                return;
            }
            this.loading = true;
            await this.$store.dispatch('groups/getItems', {where: {group_type_id: typeTab.typeId}});
            this.loadedFor[tabLabel] = true;
            this.loading = false;
        }

    }

}
</script>
<template>
  <div>
    <h1 class="flex justify-between items-center">
      Groups
      <button v-if="hasPermission('groups-manage')" class="btn btn-xs" @click="startCreateGroup">
        Create a group
      </button>
    </h1>
    <tabs-container @tab-changed="getGroupsForType">
      <tab-item v-for="def in tabDefinitions" :key="def.label" :label="def.label">
        <div v-if="loading" class="text-center w-full">
          Loading...
        </div>
        <div v-else>
          <div class="mb-2">
            Filter: <input v-model="filterString" type="text" placeholder="name,id,status,coordinator name">
          </div>
          <data-table

            v-model:sort="sort"
            v-remaining-height
            :data="filteredGroups.filter(def.filter)"
            :fields="fields"
            :row-click-handler="goToGroup"
            row-class="cursor-pointer active:bg-blue-100"
          >
            <template #cell-displayStatus="{item}">
              <badge class="text-xs" :color="item.statusColor">
                {{ item.displayStatus
                }}<span v-if="item.status.id == 1 && item.is_ep">&nbsp;-&nbsp;{{
                  item.expert_panel.currentStepAbbr
                }}</span>
              </badge>
            </template>
            <template #cell-coordinators="{value}">
              <div v-if="value.length == 0" />
              <span v-for="(coordinator, idx) in value" :key="coordinator.id">
                <span v-if="idx > 0">, </span>
                <router-link
                  :to="{name: 'PersonDetail', params: {uuid: coordinator.person.uuid}}"
                  class="link"
                  @click.stop
                >
                  {{ coordinator.person.name }}
                </router-link>
              </span>
            </template>
          </data-table>
        </div>
      </tab-item>
    </tabs-container>

    <modal-dialog v-model="showCreateForm" title="Create a New Group" size="sm">
      <SubmissionWrapper @submitted="$refs.groupForm.save()" @canceled="$refs.groupForm.cancel()">
        <GroupForm ref="groupForm" @canceled="showCreateForm = false" @saved="showCreateForm = false" />
      </SubmissionWrapper>
    </modal-dialog>
  </div>
</template>
