<template>
    <div>
        <div>
            <div class="bg-gray-200 px-4 py-2 flex justify-between border border-gray-300 rounded-t-xl">
                <h3 class="text-lg">{{application.name}}</h3>
                <div class="flex space-x-2">
                    <router-link :to="{name: 'NextAction'}" class="btn btn-sm">Add Next Action</router-link>
                    <router-link :to="{name: 'LogEntry'}" class="btn btn-sm">Add Log Entry</router-link>
                </div>
            </div>
            <div class="bg-white border border-t-0 border-gray-300 rounded-b  px-4 py-3 ">
                <next-action-banner :application="application" :next-action="application.latest_pending_next_action" v-if="hasPendingNextAction">
                </next-action-banner>

                <ep-attributes-form :application="application"></ep-attributes-form>

                <hr class="my-3">

                <progress-chart :application="application"></progress-chart>

                <hr class="my-3">

                <tabs-container>
                    
                    <tab-item label="Application">
                        <step-tabs :application="application"></step-tabs>
                    </tab-item>

                    <tab-item label="Application Log">
                        <application-log :uuid="application.uuid" :steps="[1,4]"></application-log>
                    </tab-item>

                </tabs-container>

            </div>    
        </div>
        

        <modal-dialog v-model="showModal" @closed="handleModalClosed">
            <router-view ref="modalView" @saved="hideModal" @canceled="hideModal"></router-view>
        </modal-dialog>
    </div>
</template>
<script>
import { mapGetters } from 'vuex'
import ApplicationLog from '../../components/applications/ApplicationLog'
import EpAttributesForm from '../../components/applications/EpAttributesForm'
import NextActionBanner from '../../components/next_actions/NextActionBanner'
import ProgressChart from '../../components/applications/ProgressChart'
import StepTabs from '../../components/applications/StepTabs'

export default {
    name: 'ApplicationDetail',
    components: {
        ApplicationLog,
        EpAttributesForm,
        NextActionBanner,
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
            application: 'currentItem'
        }),
        hasPendingNextAction () {
            if (typeof this.application == 'undefined') {
                console.log('this.application is undefined')
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
            // throw new Error('not implemented')
            if (typeof this.$refs.modalView.clearForm === 'function') {
                console.log('clearModalForm: call clearForm');
                this.$refs.modalView.clearForm();
            } else {
                console.log('no clearForm function on component');
            }
        }
    },
    mounted() {
        this.$store.dispatch('getApplication', this.uuid);
        this.showModal = Boolean(this.$route.meta.showModal)
    }
}
</script>