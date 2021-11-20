<template>
    <div>
        <header class="pb-4">
            <router-link class="note" :to="{name: 'GroupList'}">Groups</router-link>
            <h1 class="flex justify-between items-center">
                <div>
                    {{group.displayName}}
                    <note v-if="hasRole('super-user')" class="font-normal">
                        group.id: {{group.id}}
                        <span v-if="group.isEp()"> | expertPanel.id: {{group.expert_panel.id}}</span>
                    </note>
                </div>
                <button class="btn btn-xs" v-if="hasAnyPermission(['groups-manage', ['info-edit', group]])" @click="showEdit">Edit</button>
            </h1>
            <dictionary-row label="Chairs:">
                <template v-slot:label><strong>Chairs:</strong></template>
                <div v-if="group.chairs.length > 0">
                    {{group.chairs.map(c => c.person.name).join(', ')}}
                </div>
                <div class="text-gray-500" v-else>
                    None assigned
                </div>
            </dictionary-row>
            <dictionary-row label="Coordinators:">
                <template v-slot:label><strong>Coordinators:</strong></template>
                <div v-if="group.coordinators.length > 0">
                    <!-- <pre>{{group.coordinators}}</pre> -->
                    {{group.coordinators.map(c => c.person.name).join(', ')}}
                </div>
                <div class="text-gray-500" v-else>
                    None assigned
                </div>
            </dictionary-row>
        </header>

        <tabs-container>
            <tab-item label="Members">
                <member-list :group="group"></member-list>
                <membership-description-form :group="group" v-if="group.isVcep()" class="mt-8"></membership-description-form>
            </tab-item>
            <tab-item label="Scope" v-show="group.isEp()">
                <component :is="groupGeneList" :group="group" />
                <br> 
                <scope-description-form :group="group" v-if="group.isEp()"></scope-description-form>
            </tab-item>
            <tab-item label="Sustained Curation" v-show="group.isEp()">
                <component :is="ongoingPlansForm" :group="group" class="pb-2 mb-4 border-b"></component>
                <evidence-summaries :group="group" class="pb-2 mb-4 border-b" v-if="group.isVcep()"></evidence-summaries>
            </tab-item>
            <tab-item :visible="group.isVcep()" label="Specifications">
                Specifiations info from CSPEC will go here.
            </tab-item>
            <tab-item label="Documents">
                Group documents will go here.
            </tab-item>
            <tab-item label="Attestations">
                <attestation-gcep :group="group" class="pb-2 mb-4 border-b" v-if="group.isGcep()" :show-submit="true" />

                <attestation-reanalysis :group="group" class="pb-2 mb-4 border-b"  v-if="group.isVcep()" :show-submit="true" />
                
                <attestation-nhgri :group="group" class="pb-2 mb-4 border-b" :show-submit="true" />
            </tab-item>
            <tab-item label="Log" :visible="hasPermission('groups-manage')">
                <activity-log
                    :log-entries="logEntries"
                    :api-url="`/api/groups/${group.uuid}/activity-logs`"
                    v-bind:log-updated="getLogEntries"
                ></activity-log>
                <button class="btn btn-xs mt-1" @click="getLogEntries">Refresh</button>
            </tab-item>
            <!--
                <tab-item label="Manuscripts"></tab-item>
                <tab-item label="Funding"></tab-item>
            -->
        </tabs-container>

        <teleport to="body">
            <modal-dialog v-model="showModal" @closed="handleModalClosed" :title="this.$route.meta.title">
                <router-view ref="modalView" @saved="hideModal" @canceled="hideModal"></router-view>
            </modal-dialog>
            <modal-dialog v-model="showInfoEdit" @closed="showInfoEdit = false" title="Edit Group Info" size="sm">
                <group-form :group="group" ref="infoForm" @canceled="showInfoEdit = false"></group-form>
            </modal-dialog>
        </teleport>

    </div>
</template>
<script>
import { ref, computed, onMounted } from 'vue'
import { useStore } from 'vuex'

import {logEntries, fetchEntries} from '@/adapters/log_entry_repository';
import {hasPermission} from '@/auth_utils'

import ActivityLog from '@/components/ActivityLog'
import AttestationGcep from '@/components/expert_panels/AttestationGcep'
import AttestationNhgri from '@/components/expert_panels/AttestationNhgri'
import AttestationReanalysis from '@/components/expert_panels/AttestationReanalysis'
import EvidenceSummaries from '@/components/expert_panels/EvidenceSummaryList'
import GcepGeneList from '@/components/expert_panels/GcepGeneList'
import GcepOngoingPlansForm from '@/components/expert_panels/GcepOngoingPlansForm'
import GroupForm from '@/components/groups/GroupForm';
import MemberList from '@/components/groups/MemberList';
import MembershipDescriptionForm from '@/components/expert_panels/MembershipDescriptionForm'
import ScopeDescriptionForm from '@/components/expert_panels/ScopeDescriptionForm'
import VcepGeneList from '@/components/expert_panels/VcepGeneList'
import VcepOngoingPlansForm from '@/components/expert_panels/VcepOngoingPlansForm'
import VcepOngoingPlansFormVue from '../../components/expert_panels/VcepOngoingPlansForm.vue';

export default {
    name: 'GroupDetail',
    components: {
        ActivityLog,
        AttestationGcep,
        AttestationNhgri,
        AttestationReanalysis,
        GroupForm,
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
            showInfoEdit: false
        }
    },
    setup (props) {
        const group = computed(() => {
            return store.getters['groups/currentItem'] || {}
        });
        const groupGeneList = computed(() => {
            if (!group.value.isEp()) {
                return null;
            }
            return group.value.isVcep() ? VcepGeneList : GcepGeneList;
        })
        const store = useStore();
        const showModal = ref(false);
        const ongoingPlansForm = computed(() => {
            return group.value.isVcep() ? VcepOngoingPlansFormVue : GcepOngoingPlansForm;
        });

        const getLogEntries = async () => {
            await fetchEntries(`/api/groups/${props.uuid}/activity-logs`);
        }

        onMounted(async () => {
            await store.dispatch('groups/find', props.uuid)
                .then(() => {
                    store.commit('groups/setCurrentItemIndexByUuid', props.uuid)
                })
            if (hasPermission('groups-manage')) {
                getLogEntries();
            }
        });
        

        return {
            group,
            groupGeneList,
            showModal,
            ongoingPlansForm,
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
        }
    },
    mounted() {
        this.showModal = Boolean(this.$route.meta.showModal)
    }
}
</script>