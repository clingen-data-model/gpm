<script>
import {useStore} from 'vuex'
import {useRouter} from 'vue-router'
import {computed, ref, reactive} from 'vue'
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

        const isActive = g => g.group_status_id === 2 || g.status?.id === 2 || (g.status?.name || '').toLowerCase() === 'active';

        const emittingRow = reactive({})
        const emitting = ref(false)

        const activeIdsForDef = (def) => filteredGroups.value.filter(def.filter).filter(isActive).map(g => g.id);

        const emitCheckpoints = async (ids, { rowId } = {}) => {
          if (!Array.isArray(ids) || ids.length === 0) {
            store.commit('pushError', 'No groups to checkpoint.')
            return
          }

          if (rowId) emittingRow[rowId] = true
          else emitting.value = true

          try {
            const res = await store.dispatch('groups/checkpoints', { group_ids: ids, queue: true })
            console.log('emitCheckpoints result: ', res)
            console.log('emitCheckpoints ids: ', ids)
            const accepted = res?.accepted ?? 0
            const denied = (res?.denied_ids || []).length
            const notFound = (res?.not_found_ids || []).length
            if (accepted > 0) {
              store.commit('pushSuccess', `Queued checkpoints: ${accepted} accepted${denied ? `, ${denied} denied` : ''}${notFound ? `, ${notFound} missing` : ''}.`)
            } else {
              store.commit('pushError', `No groups accepted. ${denied ? `${denied} denied. ` : ''}${notFound ? `${notFound} not found.` : ''}`)
            }
          } catch (e) {
            store.commit('pushError', e?.response?.data?.message || 'Failed to queue Checkpoints.')
          } finally {
            if (rowId) emittingRow[rowId] = false
            else emitting.value = false
          }
        }


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
            isActive,
            emittingRow,
            emitting,
            activeIdsForDef,
            emitCheckpoints,
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
                { name: 'id',             label: 'ID',      sortable: true },
                { name: 'name',           label: 'Name',    sortable: true, resolveValue: (item) => { return item.displayName } },
                { name: 'coordinators',                     sortable: false },
                { name: 'displayStatus',  label: 'status',  sortable: true },
                { name: 'checkpoint',     label: '',        sortable: false },
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
          <div class="mb-2 flex items-center justify-between gap-3">
            <div>
              Filter: <input v-model="filterString" type="text" placeholder="name,id,status,coordinator name" class="input input-sm">
            </div>
            <div class="shrink-0">
              <button class="btn btn-sm btn-outline" :disabled="emitting || activeIdsForDef(def).length === 0" @click.stop="emitCheckpoints(activeIdsForDef(def))" title="Emit DX Checkpoints for all active groups in this tab">
                <span v-if="emitting">Queuing...</span>
                <span v-else>Sync Groups to Website ({{ activeIdsForDef(def).length }})</span>
              </button>
            </div>
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
            <template #cell-checkpoint="{ item }">
              <button
                class="btn btn-xxs btn-outline"
                :disabled="!isActive(item) || emittingRow[item.id]"
                @click.stop="emitCheckpoints([item.id], { rowId: item.id })"
                :title="isActive(item) ? 'Emit Checkpoint for this group' : 'Only Active groups can be Checkpointed'"
              >
                <span v-if="emittingRow[item.id]">Queuing…</span>
                <span v-else>Sync</span>
              </button>
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
