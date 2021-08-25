<template>
    <div>
        <static-alert variant="danger" v-if="application.contacts.length == 0" class="mb-4">
            <strong>Warning!!</strong> There are currently no contacts connected to this application!
        </static-alert >
        <card :title="`${application.name} - Current Step: ${application.current_step}`">
            <template v-slot:header-right>
                <div class="flex space-x-2">
                    <router-link :to="{name: 'NextAction'}" class="btn btn-sm">Add Next Action</router-link>
                    <router-link :to="{name: 'LogEntry'}" class="btn btn-sm">Add Log Entry</router-link>
                </div>                
            </template>
            
            <div class="md:flex md:space-x-4">
                <ep-attributes-form :application="application" class=" flex-1"></ep-attributes-form>

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
                    <step-tabs :application="application"></step-tabs>
                </tab-item>

                <tab-item label="Application Log">
                    <application-log :uuid="application.uuid"></application-log>
                </tab-item>

                <!-- <tab-item label="Dev Tools">
                    <pre>{{this.application}}</pre>
                </tab-item> -->
                <tab-item label="Advanced">
                    <h2 class="block-title">Advanced actions and controls</h2>
                    <router-link :to="{name: 'ConfirmDeleteApplication'}" class="btn red">
                        Delete Application and all associated information.
                    </router-link>
                </tab-item>
            </tabs-container>

        </card>

        <modal-dialog v-model="showModal" @closed="handleModalClosed">
            <router-view ref="modalView" @saved="hideModal" @canceled="hideModal"></router-view>
        </modal-dialog>
    </div>
</template>
<script>
import { mapGetters } from 'vuex'
import ApplicationLog from '@/components/applications/ApplicationLog'
import EpAttributesForm from '@/components/applications/EpAttributesForm'
// import NextActionBanner from '@/components/next_actions/NextActionBanner'
import NextActions from '@/components/next_actions/NextActions'
import ProgressChart from '@/components/applications/ProgressChart'
import StepTabs from '@/components/applications/StepTabs'

export default {
    name: 'ApplicationDetail',
    components: {
        ApplicationLog,
        EpAttributesForm,
        NextActions,
        ProgressChart,
        StepTabs
    },
    props: {
        uuid: {
            type: String,
            required: true
        }
    },
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
            application: 'applications/currentItem'
        }),
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
    },
    mounted() {
        this.$store.dispatch('applications/getApplication', this.uuid);
        this.showModal = Boolean(this.$route.meta.showModal)
    }
}
</script>