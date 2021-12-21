<template>
    <div>
        <group-detail-header :group="group" @showEdit="showEdit"></group-detail-header>
        
        <application-summary 
            :group="group" 
            v-if="group.isApplying"
        />

        <tabs-container @tab-changed="handleTabChange">
            <tab-item label="Members">
                <member-list :group="group"></member-list>
                <submission-wrapper 
                    v-if="group.isVcep()" 
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
            <tab-item label="Scope" :visible="group.isEp()">
                <h3>Plans for Ongoing Gene Review and Reanalysis and Discrepancy Resolution</h3>
                <submission-wrapper 
                    v-if="group.isEp()" 
                    @submitted="$refs.groupGeneListRef.save()" 
                    @canceled="$refs.groupGeneListRef.cancel()"
                    :show-controls="editingGenes"
                >
                    <component ref="groupGeneListRef" :is="groupGeneList" v-model:editing="editingGenes" />
                </submission-wrapper>
                <br> 
                <submission-wrapper 
                    :visible="group.isEp()"
                    @submitted="submitForm('saveScopeDescription', 'editingScopeDescription')" 
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
            <tab-item label="Sustained Curation" :visible="group.isEp()">
                <submission-wrapper
                    @submitted="submitForm('saveOngoingPlansForm')"
                    @canceled="cancelForm()"
                     class="pb-4"
                >
                    <component
                        ref="ongoingPlansForm"
                        :is="ongoingPlansFormComponent"
                        :errors="errors"
                    />
                </submission-wrapper>
                <section v-if="group.isVcep()">
                    <header>  
                        <h3>Example Evidence Summaries</h3>
                    </header>
                    <evidence-summaries :group="group" class="pb-2 mb-4 border-b" />
                </section>
            </tab-item>
            <tab-item :visible="group.isVcep()" label="Specifications">
                <cspec-summary />
            </tab-item>
            <tab-item label="Documents">
                Group documents will go here.
            </tab-item>
            <tab-item label="Attestations" :visible="group.isEp()">
                <attestation-gcep class="pb-2 mb-4 border-b" v-if="group.isGcep()" :disabled="true" />

                <h3>Reanalysis &amp; Discrepancy Resolution</h3>
                <attestation-reanalysis class="pb-2 mb-4 border-b"  v-if="group.isVcep()" :disabled="true" />                
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
                <h2 class="mb-4">Here be dragons.  Proceed with caution.</h2>
                <button class="btn btn red" @click="initDelete">Delete Group</button>
            </tab-item>
        </tabs-container>

        <teleport to="body">
            <modal-dialog v-model="showModal" @closed="handleModalClosed" :title="this.$route.meta.title">
                <router-view ref="modalView" @saved="hideModal" @canceled="hideModal"></router-view>
            </modal-dialog>
            <modal-dialog v-model="showInfoEdit" @closed="showInfoEdit = false" title="Edit Group Info" size="sm">
                <submission-wrapper @submitted="$refs.infoForm.save()" @canceled="$refs.infoForm.cancel()">
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
                        You are about to delete {{group.displayName}} and all associated data.  
                    </p>
                    <p>This cannot be undone.</p>
                </div>
                <button-row 
                    submit-text="Delete this group."
                    @submitted="deleteGroup" @canceled="showConfirmDelete = false"></button-row>
            </modal-dialog>
        </teleport>

    </div>
</template>
<script>
import { ref, computed, onMounted} from 'vue'
import { useStore } from 'vuex'

import Group from '@/domain/group';

import {logEntries, fetchEntries} from '@/adapters/log_entry_repository';
import {hasPermission} from '@/auth_utils'

import ActivityLog from '@/components/log_entries/ActivityLog'
import ApplicationSummary from '@/components/groups/ApplicationSummary'
import AttestationGcep from '@/components/expert_panels/AttestationGcep'
import AttestationNhgri from '@/components/expert_panels/AttestationNhgri'
import AttestationReanalysis from '@/components/expert_panels/AttestationReanalysis'
import CspecSummary from '@/components/expert_panels/CspecSummary'
import EvidenceSummaries from '@/components/expert_panels/EvidenceSummaryList'
import GcepGeneList from '@/components/expert_panels/GcepGeneList'
import GcepOngoingPlansForm from '@/components/expert_panels/GcepOngoingPlansForm'
import GroupForm from '@/components/groups/GroupForm';
import GroupDetailHeader from './GroupDetailHeader';
import MemberList from '@/components/groups/MemberList';
import MembershipDescriptionForm from '@/components/expert_panels/MembershipDescriptionForm'
import ScopeDescriptionForm from '@/components/expert_panels/ScopeDescriptionForm'
import VcepGeneList from '@/components/expert_panels/VcepGeneList'
import VcepOngoingPlansForm from '@/components/expert_panels/VcepOngoingPlansForm'
import VcepOngoingPlansFormVue from '../../components/expert_panels/VcepOngoingPlansForm.vue';

import isValidationError from '../../http';

export default {
    name: 'GroupDetail',
    components: {
        ActivityLog,
        ApplicationSummary,
        AttestationGcep,
        AttestationNhgri,
        AttestationReanalysis,
        CspecSummary,
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
    },
    props: {
        uuid: {
            type: String,
            required: true
        }
    },
    data () {
        return {
            showInfoEdit: false,
            editingExpertise: false,
            editingScopeDescription: false,
            editingGenes: false,
            showConfirmDelete: false,
            ongoingPlansErrors: {},
            errors: {}
        }
    },
    setup (props) {
        const store = useStore();
        const showModal = ref(false);

        const group = computed ({
            get: function() {
                return store.getters['groups/currentItemOrNew']
            },
            set: function (value) {
                store.commit('groups/addItem', value);
            }
        })

        const groupGeneList = computed(() => {
            if (!group.value.isEp()) {
                return null;
            }
            return group.value.isVcep() ? VcepGeneList : GcepGeneList;
        })
        
        const ongoingPlansFormComponent = computed(() => {
            return group.value.isVcep() ? VcepOngoingPlansFormVue : GcepOngoingPlansForm;
        });

        const getLogEntries = async () => {
            await fetchEntries(`/api/groups/${props.uuid}/activity-logs`);
        }

        onMounted(async () => {
            store.dispatch('groups/find', props.uuid)
                .then(() => {
                    store.commit('groups/setCurrentItemIndexByUuid', props.uuid)
                    store.dispatch('groups/getGenes', group.value);
                    if (group.value.isVcep()) {
                        store.dispatch('groups/getEvidenceSummaries', group.value);
                    }
                })
        });

        return {
            group,
            groupGeneList,
            showModal,
            ongoingPlansFormComponent,
            logEntries,
            getLogEntries,
        }
    },
    watch: {
        $route() {
            this.showModal = this.$route.meta.showModal 
                                ? Boolean(this.$route.meta.showModal) 
                                : false;
        }
    },
    methods: {
        hideModal () {
            this.$router.replace({name: 'GroupDetail', params: {uuid: this.uuid}});
        },
        handleModalClosed (evt) {
            this.clearModalForm(evt);
            this.$router.push({name: 'GroupDetail', params: {uuid: this.uuid}});
        },
        clearModalForm () {
            if (typeof this.$refs.modalView.clearForm === 'function') {
                this.$refs.modalView.clearForm();
            }
        },
        showEdit () {
            this.showInfoEdit = true;
        },
        cancelForm (modelAttr) {
            this[modelAttr] = false;
            this.errors = {};
            this.revertGroupChanges();
        },
        async submitForm (saveMethod, modelAttr) {
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
        saveMembershipDescription () {
            return this.$store.dispatch(
                'groups/membershipDescriptionUpdate', 
                {
                    uuid: this.group.uuid, 
                    membershipDescription: this.group.expert_panel.membership_description
                }
            ).then(() => {
                this.$store.commit('pushSuccess', 'Description of expertise updated.')
            });
        },
        saveScopeDescription () {
            return this.$store.dispatch(
                'groups/scopeDescriptionUpdate', 
                {
                    uuid: this.group.uuid, 
                    scopeDescription: this.group.expert_panel.scope_description
                }
            ).then(response => {
                this.$store.commit('pushSuccess', 'Description of scope updated.')
                return response;
            });
        },
        saveOngoingPlansForm () {
            const {uuid, expert_panel: expertPanel} = this.group;
            return this.$store.dispatch('groups/curationReviewProtocolUpdate', {uuid, expertPanel})
                    .then(() => {
                        this.$store.commit('pushSuccess', 'Curation review protocol updated.')
                    });
        },
        revertGroupChanges () {
            this.errors = {};
            return this.$store.dispatch('groups/find', this.group.uuid)
                    .then(() => {
                        this.$store.commit('pushInfo', 'Update canceled.')
                    });
        },
        handleTabChange (tabName) {
            if (tabName == 'Log' && hasPermission('groups-manage')) {
                this.getLogEntries();
            }
        },
        initDelete() {
            this.showConfirmDelete = true;
        },
        deleteGroup () {
            this.$store.dispatch('groups/delete', this.group.uuid);
            this.$store.commit('pushSuccess', 'Group deleted.');
            this.$router.push({name: 'GroupList'})
        }
    },
    mounted() {
        this.showModal = Boolean(this.$route.meta.showModal);
        this.showConfirmDelete = false;
    },
    beforeUnmount() {
        this.$store.commit('groups/clearCurrentItem');
    },
}
</script>