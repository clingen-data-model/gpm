<template>
    <div>
        <static-alert variant="danger" v-if="!group.hasContacts" class="mb-4">
            <strong>Warning!!</strong> There are currently no contacts connected to this application!
        </static-alert >
        <router-link class="note"
            :to="{name: 'GroupDetail', params: {uuid: group.uuid}}"
            v-if="group.uuid"
        >
            {{group.displayName}}
        </router-link>
        <card>
            <template v-slot:title>
                <h2>{{application.full_name}} - Current Step: {{application.current_step}}</h2>
            </template>
            <template v-slot:header-right>
                <div class="flex space-x-2">
                    <router-link :to="{name: 'NextAction'}" class="btn btn-sm">Add Next Action</router-link>
                    <router-link :to="{name: 'LogEntry'}" class="btn btn-sm">Add Log Entry</router-link>
                </div>                
            </template>
            
            <div class="md:flex md:space-x-4">
                <!-- <ep-attributes-form :application="application" class=" flex-1"></ep-attributes-form> -->
                <basic-info-data></basic-info-data>
                <div class="flex-1 space-y-2 md:border-l md:px-4 md:py-2">
                    <next-actions :next-actions="application.next_actions" v-if="application.next_actions"></next-actions>
                </div>
            </div>

            <progress-chart 
                :application="application" 
                class="py-4 border-t border-b border-gray-300 my-6"
            ></progress-chart>

            <tabs-container>
                <tab-item label="Application">
                    <step-tabs :application="application" @updated="getGroup" />
                </tab-item>

                <tab-item label="Application Log">
                    <application-log :uuid="application.uuid"></application-log>
                </tab-item>

                <tab-item label="Advanced">
                    <h2 class="block-title">Advanced actions and controls</h2>
                    <router-link :to="{name: 'ConfirmDeleteApplication'}" class="btn red">
                        Delete Application and all associated information.
                    </router-link>
                </tab-item>
            </tabs-container>

        </card>

        <teleport to="body">
            <modal-dialog 
                v-model="showModal" 
                @closed="handleModalClosed"
                :title="$route.meta.title"
            >
                <router-view ref="modalView" @saved="hideModal" @canceled="hideModal"></router-view>
            </modal-dialog>
        </teleport>

        <note>{{application.id}}</note>
    </div>
</template>
<script>
import { mapGetters } from 'vuex'
import ApplicationLog from '@/components/applications/ApplicationLog.vue'
// import EpAttributesForm from '@/components/applications/EpAttributesForm.vue'
// import NextActionBanner from '@/components/next_actions/NextActionBanner.vue'
import NextActions from '@/components/next_actions/NextActions.vue'
import ProgressChart from '@/components/applications/ProgressChart.vue'
import StepTabs from '@/components/applications/StepTabs.vue'
import BasicInfoData from '@/components/applications/BasicInfoData.vue'

export default {
    name: 'ApplicationDetail',
    components: {
        ApplicationLog,
        // EpAttributesForm,
        NextActions,
        ProgressChart,
        StepTabs,
        BasicInfoData
    },
    props: {
        uuid: {
            type: String,
            required: true
        }
    },
    data() {
        return {
            showModal: false
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
        }
    },
    methods: {
        hideModal () {
            this.$router.replace({name: 'ApplicationDetail', params: {uuid: this.uuid}});
        },
        handleModalClosed (evt) {
            this.clearModalForm(evt);
            this.$router.push({name: 'ApplicationDetail', params: {uuid: this.uuid}});
        },
        clearModalForm () {
            if (typeof this.$refs.modalView.clearForm === 'function') {
                this.$refs.modalView.clearForm();
            }
        },
        async getGroup () {
            await this.$store.dispatch('groups/findAndSetCurrent', this.uuid);
            this.$store.dispatch('groups/getDocuments', this.group);
            this.$store.dispatch('groups/getNextActions', this.group);
            this.$store.dispatch('groups/getSubmissions', this.group);
            this.$store.dispatch('groups/getMembers', this.group);
        },
    },
    mounted() {
        this.getGroup();
        this.showModal = Boolean(this.$route.meta.showModal)
    }
}
</script>