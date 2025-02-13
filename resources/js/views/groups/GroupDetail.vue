<template>
  <div>
    <annual-update-alert
      :group="group"
      v-if="
        hasAnyPermission([
          'annual-updates-mange',
          ['annual-update-manage', group],
        ])
      "
      class="mb-2"
    />

    <sustained-curation-review-alert
      v-if="
        needsToReviewSustainedCuration &&
        hasAnyPermission([
          'ep-applications-manage',
          ['application-edit', group],
        ])
      "
      :group="group"
      class="mb-2"
    />

    <group-detail-header
      :group="group"
      @showEdit="showEdit"
    ></group-detail-header>


    <div class="flex space-x-4">
      <div class="flex-grow mt-4">
        <application-summary :group="group" v-if="group.isApplying" />
        <tabs-container @tab-changed="handleTabChange">
          <tab-item label="Summary Description">
            <submission-wrapper
              :visible="group.is_ep"
              @submitted="
                submitForm('saveDescription', 'editingDescription')
              "
              @canceled="cancelForm('editingDescription')"
              :show-controls="editingDescription"
            >
              <group-description-form
                ref="descriptionRef"
                v-model:editing="editingDescription"
                :errors="errors"
              />
            </submission-wrapper>
          </tab-item>
          <tab-item label="Members">
            <member-list :group="group"></member-list>
            <submission-wrapper
                            v-if="group.is_vcep_or_scvcep"
                            @submitted="submitForm('saveMembershipDescription', 'editingExpertise')"
              @canceled="cancelForm('editingExpertise')"
              :show-controls="editingExpertise"
            >
              <membership-description-form
                ref="membershipDescriptionFormRef"
                class="mt-8"
                v-model:editing="editingExpertise"
                :errors="errors"
              />
            </submission-wrapper>
          </tab-item>

          <tab-item label="Scope" :visible="group.is_ep">
            <h3>
              Plans for Ongoing Gene Review and Reanalysis and Discrepancy
              Resolution
            </h3>
            <submission-wrapper
              v-if="group.is_ep"
              @submitted="$refs.groupGeneListRef.save()"
              @canceled="$refs.groupGeneListRef.cancel()"
              :show-controls="editingGenes"
            >
              <component
                ref="groupGeneListRef"
                :is="groupGeneList"
                v-model:editing="editingGenes"
              />
            </submission-wrapper>
            <br />
            <submission-wrapper
              :visible="group.is_ep"
              @submitted="
                submitForm('saveScopeDescription', 'editingScopeDescription')
              "
              @canceled="cancelForm('editingScopeDescription')"
              :show-controls="editingScopeDescription"
            >
              <scope-description-form
                ref="scopeDescriptionRef"
                v-model:editing="editingScopeDescription"
                :errors="errors"
              />
            </submission-wrapper>
          </tab-item>

          <tab-item label="Sustained Curation" :visible="group.is_ep"
            class="relative"
          >
            <div
              class="bg-white bg-opacity-50 absolute top-0 left-0 right-0 bottom-0"
              v-if="!group.expert_panel.pilotSpecificationsIsApproved && group.is_vcep_or_scvcep"
            ></div>
            <static-alert
              variant="info"
              v-if="!group.expert_panel.pilotSpecificationsIsApproved && group.is_vcep_or_scvcep"
            >
              You can complete these sections after your first Specifications Pilot
              has been approved.
            </static-alert>
            <submission-wrapper
              @submitted="submitForm('saveOngoingPlansForm')"
              @canceled="cancelForm()"
              class="pb-4"
              :showControls="
                hasAnyPermission([
                  'ep-applications-manage',
                  ['application-edit', group],
                ]) && group.expert_panel.pilotSpecificationsIsApproved
              "
            >
              <component
                ref="ongoingPlansForm"
                :is="ongoingPlansFormComponent"
                :errors="errors"
                :readonly="!group.expert_panel.pilotSpecificationsIsApproved"
              />
            </submission-wrapper>
            <section v-if="group.is_vcep_or_scvcep">
              <header>
                <h3>Example Evidence Summaries</h3>
              </header>
              <evidence-summaries
                :group="group"
                :readonly="!group.expert_panel.pilotSpecificationsIsApproved"
                class="pb-2 mb-4 border-b"
              />
            </section>
          </tab-item>

          <tab-item label="Specifications" :visible="group.is_vcep_or_scvcep">
            <div class="relative">
              <div
                class="bg-white bg-opacity-50 absolute top-0 left-0 right-0 bottom-0"
                v-if="!group.expert_panel.defIsApproved && group.is_vcep_or_scvcep"
              ></div>
              <static-alert
                variant="info"
                v-if="!group.expert_panel.defIsApproved && group.is_vcep_or_scvcep"
              >
                You can complete these sections after your Group Definition
                has been approved.
              </static-alert>
              <div>
                <specifications-section
                  :doc-type-id="group.expert_panel.draftSpecApproved ? [2,3,4,7] : 2"
                  :step="group.expert_panel.draftSpecApproved ? 3 : 2"
                />
            </div>

            </div>
          </tab-item>

          <tab-item label="Documents" v-if="userInGroup(group) || hasPermission('groups-manage')">
            <group-documents></group-documents>
            <note>Documents are only available to members of this group.</note>
          </tab-item>

          <tab-item label="Attestations" :visible="group.is_ep">
            <note
              >The attestations below are read only. Attestations can only be
              completed during the application process.</note
            >
            <attestation-gcep
              class="pb-2 mb-4 border-b"
              v-if="group.is_gcep"
              :disabled="true"
            />

            <h3>Reanalysis &amp; Discrepancy Resolution</h3>
            <attestation-reanalysis
              class="pb-2 mb-4 border-b"
              v-if="group.is_vcep_or_scvcep"
              :disabled="true"
            />
            <h3>NHGRI Data Availability</h3>
            <attestation-nhgri class="pb-2 mb-4 border-b" :disabled="true" />
          </tab-item>

          <tab-item label="Log" :visible="hasPermission('groups-manage') || userInGroup(group)">
            <activity-log
              :log-entries="logEntries"
              :api-url="`/api/groups/${group.uuid}/activity-logs`"
              v-bind:log-updated="getLogEntries"
            ></activity-log>
            <button class="btn btn-xs mt-1" @click="getLogEntries">Refresh</button>
          </tab-item>

          <tab-item label="Admin" :visible="hasPermission('groups-manage')">
            <div v-if="group.isApplying && group.is_ep">
              <h2 class="pb-2 border-b mb-4">Application</h2>
              <progress-chart
                :application="group.expert_panel"
                class="pb-4 border-b border-gray-300 mb-6"
              ></progress-chart>
              <step-tabs
                :application="group.expert_panel"
                @stepApproved="getGroup"
                v-if="group.is_ep"
              />
              <hr />
            </div>
            <section
              class="border my-4 p-4 rounded"
              v-if="group.is_ep"
            >
              <h2 class="mb-4">Annual Update</h2>
                <button v-if="showCreateAnnualUpdateButton"
                  class="btn" @click="createAnnualUpdateForLatestWindow"
                >
                  Create Annual Update for latest window.
                </button>
                <a
                  v-else-if="group.expert_panel.annualUpdate"
                  :href="`/annual-updates/${this.group.expert_panel.annualUpdate.id}`"
                  target="annual update"
                >
                  View Annual Update for {{this.group.expert_panel.annualUpdate.window.for_year}}
                </a>
            </section>
            <section class="border my-4 p-4 bg-red-100 border-red-200 rounded">
              <h2 class="mb-4 text-red-800">
                Here be dragons. Proceed with caution.
              </h2>
              <button class="btn btn red" @click="initDelete">Delete Group</button>
            </section>
            <section
              class="border my-4 p-4 rounded"
              v-if="$store.state.systemInfo.env !== 'production'"
            >
              <h2 class="mb-4">
                Dev tools
                <span class="text-gray-500"> - don't use if you don't know.</span>
              </h2>
              <button
                class="btn"
                v-if="group.is_vcep_or_scvcep && group.expert_panel.has_approved_pilot"
                @click="fakeCspecPilotApproved"
              >
                Fake a Pilot Approved Message
              </button>
            </section>
          </tab-item>
        </tabs-container>
      </div>
      <div class="md:w-1/5 lg:w-1/4" v-if="group.hasChildren">
        <subgroup-list />
      </div>

    </div>

    <teleport to="body">
      <modal-dialog
        v-model="showModal"
        @closed="handleModalClosed"
        :title="this.$route.meta.title"
      >
        <router-view
          ref="modalView"
          @saved="hideModal"
          @canceled="hideModal"
        ></router-view>
      </modal-dialog>
      <modal-dialog
        v-model="showInfoEdit"
        @closed="showInfoEdit = false"
        title="Edit Group Info"
        size="sm"
      >
        <submission-wrapper
          @submitted="$refs.infoForm.save()"
          @canceled="$refs.infoForm.cancel()"
        >
          <group-form
            ref="infoForm"
            @canceled="showInfoEdit = false"
            @saved="showInfoEdit = false"
          />
        </submission-wrapper>
      </modal-dialog>
      <modal-dialog v-model="showConfirmDelete" title="Delete This Group?">
        <div class="text-lg">
          <p>
            You are about to delete {{ group.displayName }} and all associated
            data.
          </p>
          <p>This cannot be undone.</p>
        </div>
        <button-row
          submit-text="Delete this group."
          @submitted="deleteGroup"
          @canceled="showConfirmDelete = false"
        ></button-row>
      </modal-dialog>
    </teleport>
  </div>
</template>
<script>
import { ref, computed, onMounted, watch } from "vue";
import { useStore } from "vuex";

import { logEntries, fetchEntries } from "@/adapters/log_entry_repository";
import { hasPermission } from "@/auth_utils";

import ActivityLog from "@/components/log_entries/ActivityLog";
import ApplicationSummary from "@/components/groups/ApplicationSummary";
import AttestationGcep from "@/components/expert_panels/AttestationGcep";
import AttestationNhgri from "@/components/expert_panels/AttestationNhgri";
import AttestationReanalysis from "@/components/expert_panels/AttestationReanalysis";
import SpecificationsSection from "@/components/expert_panels/SpecificationsSection";
import EvidenceSummaries from "@/components/expert_panels/EvidenceSummaryList";
import GcepGeneList from "@/components/expert_panels/GcepGeneList";
import GcepOngoingPlansForm from "@/components/expert_panels/GcepOngoingPlansForm";
import GroupDescriptionForm from "@/components/groups/GroupDescriptionForm";
import GroupForm from "@/components/groups/GroupForm";
import GroupDetailHeader from "./GroupDetailHeader";
import MemberList from "@/components/groups/MemberList";
import MembershipDescriptionForm from "@/components/expert_panels/MembershipDescriptionForm";
import ScopeDescriptionForm from "@/components/expert_panels/ScopeDescriptionForm";
import VcepGeneList from "@/components/expert_panels/VcepGeneList";
import VcepOngoingPlansForm from "@/components/expert_panels/VcepOngoingPlansForm";
import GroupDocuments from "./GroupDocuments";
import AnnualUpdateAlert from "@/components/groups/AnnualUpdateAlert";
import StepTabs from "@/components/applications/StepTabs";
import ProgressChart from "@/components/applications/ProgressChart";
import SustainedCurationReviewAlert from "@/components/alerts/SustainedCurationReviewAlert";
import SubgroupList from '@/components/groups/SubgroupList.vue'

import { api, isValidationError } from "../../http";

export default {
  name: "GroupDetail",
  components: {
    // ApplicationDetail,
    ActivityLog,
    ApplicationSummary,
    AttestationGcep,
    AttestationNhgri,
    AttestationReanalysis,
    GroupDescriptionForm,
    SpecificationsSection,
    GroupDocuments,
    GroupForm,
    GroupDetailHeader,
    EvidenceSummaries,
    GcepGeneList,
    GcepOngoingPlansForm,
    MemberList,
    MembershipDescriptionForm,
    ScopeDescriptionForm,
    VcepGeneList,
    VcepOngoingPlansForm,
    AnnualUpdateAlert,
    StepTabs,
    ProgressChart,
    SustainedCurationReviewAlert,
    SubgroupList
  },
  props: {
    uuid: {
      type: String,
      required: true,
    },
  },
  data() {
    return {
      showInfoEdit: false,
      editingExpertise: false,
      editingDescription: false,
      editingScopeDescription: false,
      editingGenes: false,
      showConfirmDelete: false,
      ongoingPlansErrors: {},
      errors: {},
    };
  },
  computed: {
    showCreateAnnualUpdateButton () {
      return this.hasPermission('annual-updates-manage')
        && this.group.expert_panel
        && !this.group.expert_panel.annualUpdate;
    }
  },
  setup(props) {
    const store = useStore();
    const showModal = ref(false);

    const group = computed({
      get: function () {
        return store.getters["groups/currentItemOrNew"];
      },
      set: function (value) {
        store.commit("groups/addItem", value);
      },
    });

    const groupGeneList = computed(() => {
      if (!group.value.is_ep) {
        return null;
      }
      return group.value.is_vcep_or_scvcep ? VcepGeneList : GcepGeneList;
    });

    const ongoingPlansFormComponent = computed(() => {
      return group.value.is_vcep_or_scvcep ? VcepOngoingPlansForm : GcepOngoingPlansForm;
    });

    const getLogEntries = async () => {
      await fetchEntries(`/api/groups/${props.uuid}/activity-logs`);
    };

    const getGroup = () => {
      store.dispatch("groups/find", props.uuid).then(() => {
        store.commit("groups/setCurrentItemIndexByUuid", props.uuid);
        store.dispatch('groups/getChildren', group.value);
        if (group.value.is_ep) {
          store.dispatch("groups/getGenes", group.value);
          store.dispatch("groups/getDocuments", group.value);
          if (group.value.is_vcep_or_scvcep) {
            store.dispatch("groups/getEvidenceSummaries", group.value);
          }
          store.dispatch("groups/getPendingTasks", group.value);
          store.dispatch("groups/getAnnualUpdate", group.value);
        }
      });
    };

    const needsToReviewSustainedCuration = computed(() => {
      return (
        group.value.pendingTasks &&
        group.value.pendingTasks.filter((pt) => pt.task_type_id === 1).length >
          0
      );
    });

    watch(() => props.uuid, () => getGroup(), {immediate: true})

    onMounted(async () => {
      // getGroup();
    });

    return {
      group,
      groupGeneList,
      showModal,
      ongoingPlansFormComponent,
      logEntries,
      needsToReviewSustainedCuration,
      getLogEntries,
      getGroup,
    };
  },
  watch: {
    $route() {
      this.showModal = this.$route.meta.showModal
        ? Boolean(this.$route.meta.showModal)
        : false;
    },
  },
  methods: {
    hideModal() {
      this.$router.replace({
        name: "GroupDetail",
        params: { uuid: this.uuid },
      });
    },
    handleModalClosed(evt) {
      this.clearModalForm(evt);
      this.$router.push({ name: "GroupDetail", params: { uuid: this.uuid } });
    },
    clearModalForm() {
      if (typeof this.$refs.modalView.clearForm === "function") {
        this.$refs.modalView.clearForm();
      }
    },
    showEdit() {
      this.showInfoEdit = true;
    },
    cancelForm(modelAttr) {
      this[modelAttr] = false;
      this.errors = {};
      this.revertGroupChanges();
    },
    async submitForm(saveMethod, modelAttr) {
      try {
        await this[saveMethod].apply(this.group);
        this[modelAttr] = false;
      } catch (error) {
        if (isValidationError(error)) {
          this.errors = error.response.data.errors;
          return;
        }
        throw error;
      }
    },
    saveDescription() {
      return this.$store
        .dispatch("groups/descriptionUpdate", {
          uuid: this.group.uuid,
          description: this.group.description,
        })
        .then(() => {
          this.$store.commit(
            "pushSuccess",
            "Description updated."
          );
        });
    },
    saveMembershipDescription() {
      return this.$store
        .dispatch("groups/membershipDescriptionUpdate", {
          uuid: this.group.uuid,
          membershipDescription: this.group.expert_panel.membership_description,
        })
        .then(() => {
          this.$store.commit(
            "pushSuccess",
            "Description of expertise updated."
          );
        });
    },
    saveScopeDescription() {
      return this.$store
        .dispatch("groups/scopeDescriptionUpdate", {
          uuid: this.group.uuid,
          scopeDescription: this.group.expert_panel.scope_description,
        })
        .then((response) => {
          this.$store.commit("pushSuccess", "Description of scope updated.");
          return response;
        });
    },
    saveOngoingPlansForm() {
      const { uuid, expert_panel: expertPanel } = this.group;
      return this.$store
        .dispatch("groups/curationReviewProtocolUpdate", { uuid, expertPanel })
        .then(() => {
          this.$store.commit(
            "pushSuccess",
            "Curation review protocol updated."
          );
        });
    },
    revertGroupChanges() {
      this.errors = {};
      return this.$store.dispatch("groups/find", this.group.uuid).then(() => {
        this.$store.commit("pushInfo", "Update canceled.");
      });
    },
    handleTabChange(tabName) {
      if (tabName == "Log" && hasPermission("groups-manage")) {
        this.getLogEntries();
      }
    },
    initDelete() {
      this.showConfirmDelete = true;
    },
    deleteGroup() {
      this.$store.dispatch("groups/delete", this.group.uuid);
      this.$store.commit("pushSuccess", "Group deleted.");
      this.$router.push({ name: "GroupList" });
    },
    async fakeCspecPilotApproved() {
      try {
        await api.post(`/api/groups/${this.uuid}/dev/fake-pilot-approved`);
        this.$store.dispatch("groups/getPendingTasks", this.group);
      } catch (e) {
        if (e.response.status == 418) {
          this.$store.commit("pushError", e.response.data);
        }
      }
    },
    createAnnualUpdateForLatestWindow () {
      this.$store.dispatch('groups/createAnnualUpdateForLatestWindow', this.group);
    }
  },
  mounted() {
    this.showModal = Boolean(this.$route.meta.showModal);
    this.showConfirmDelete = false;
  },
  beforeUnmount() {
    this.$store.commit("groups/clearCurrentItem");
  },
};
</script>
