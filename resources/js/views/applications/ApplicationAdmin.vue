<script>
import { mapGetters } from 'vuex'
import ApplicationLog from '@/components/applications/ApplicationLog.vue'
import NextActions from '@/components/next_actions/NextActions.vue'
import ProgressChart from '@/components/applications/ProgressChart.vue'
import StepTabs from '@/components/applications/StepTabs.vue'
import BasicInfoData from '@/components/applications/BasicInfoData.vue'

export default {
    name: 'ApplicationDetail',
    components: {
        ApplicationLog,
        NextActions,
        ProgressChart,
        StepTabs,
        BasicInfoData
    },
    props: {
        loading: {
            type: Boolean,
            default: false
        }
    },
    emits: ['updated'],
    data() {
        return {
            showModal: false,
        }
    },
    watch: {
        $route() {
                this.showModal = this.$route.meta.showModal
                                    ? Boolean(this.$route.meta.showModal)
                                    : false;
        }
    },
    computed: {
        ...mapGetters({
            group: 'groups/currentItemOrNew'
        }),
        application () {
            return this.group.expert_panel;
        },
        hasPendingNextAction () {
            if (typeof this.application == 'undefined') {
                return false;
            }

            return this.application.latest_pending_next_action
        },
        breadcrumbs () {
            if (!this.group.uuid) {
                return [];
            }
            return [{label: this.group.displayName, route: {name: 'GroupDetail', params: {uuid: this.group.uuid}}}];
        }
    },
    mounted() {
        this.showModal = Boolean(this.$route.meta.showModal)
    },
    methods: {
        hideModal () {
            this.$router.replace({name: 'ApplicationDetail', params: {uuid: this.group.uuid}});
        },
        handleModalClosed (evt) {
            this.clearModalForm(evt);
            this.$router.push({name: 'ApplicationDetail', params: {uuid: this.group.uuid}});
        },
        clearModalForm () {
            if (typeof this.$refs.modalView.clearForm === 'function') {
                this.$refs.modalView.clearForm();
            }
        },
    }
}
</script>

<template>
  <div>
    <static-alert v-if="!loading && !group.hasContacts" variant="danger" class="mb-4">
      <strong>Warning!!</strong> There are currently no contacts connected to this application!
    </static-alert>
    <ScreenTemplate :title="group.displayName" :breadcrumbs="breadcrumbs">
      <template #header-dev>
        <note>
          Group ID: {{ group.id }}
          |
          Expert Panel ID: {{ group.expert_panel.id }}
        </note>
      </template>
      <template #header-right>
        <div class="flex space-x-2">
          <router-link :to="{name: 'NextAction'}" class="btn btn-sm">
            Add Next Action
          </router-link>
          <router-link :to="{name: 'LogEntry'}" class="btn btn-sm">
            Add Log Entry
          </router-link>
        </div>
      </template>

      <div class="md:flex md:space-x-4">
        <BasicInfoData class="w-1/2 screen-block" />
        <NextActions
          v-if="application.next_actions"
          :next-actions="application.next_actions"
          class="w-1/2 space-y-2 md:px-4 md:py-2 bg-white border-b border-gray-200 "
        />
      </div>

      <ProgressChart
        :application="application"
        class="py-4 screen-block"
      />

      <tabs-container>
        <tab-item label="Application">
          <StepTabs
            :application="application"
            @updated="$emit('updated')"
            @approved="$emit('updated')"
          />
        </tab-item>

        <tab-item label="Application Log">
          <ApplicationLog :uuid="application.uuid" />
        </tab-item>

        <tab-item label="Advanced">
          <h2 class="block-title">
            Advanced actions and controls
          </h2>
          <router-link :to="{name: 'ConfirmDeleteApplication'}" class="btn red">
            Delete Application and all associated information.
          </router-link>
        </tab-item>
      </tabs-container>
    </ScreenTemplate>
    <teleport to="body">
      <modal-dialog
        v-model="showModal"
        :title="$route.meta.title"
        @closed="handleModalClosed"
      >
        <router-view ref="modalView" @saved="hideModal" @canceled="hideModal" />
      </modal-dialog>
    </teleport>
  </div>
</template>
