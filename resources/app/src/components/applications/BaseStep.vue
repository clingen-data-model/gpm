<template>
    <div>
        <slot name="document">
            <div class="mb-6">
                <div class="flex justify-between text-lg font-bold pb-2 mb-2 border-b">
                    <h3 class="">{{documentName}}</h3>
                    <div v-if="dateApproved">Appproved: {{dateApproved}}</div>
                </div>
                <document-manager
                    class="border-b"
                    :application="application"
                    :document-type-id="documentType"
                    :getsReviewd="documentGetsReviewed"
                    :step="step"
                ></document-manager>
            </div>
        </slot>

        <!-- Approve step -->
        <div  
            class="border border-l-0 border-r-0 py-4 mb-6" 
            v-if="!application.stepIsApproved(step)"
        >
            <button 
                class="btn btn-lg w-full" 
                @click="startApproveStep"
                :disabled="!isCurrentStep"
                :title="isCurrentStep ? 'Approve this step' : 'You can only approve the application\'s current step'"
            >
                {{approveButtonLabel}}
            </button>
            <modal-dialog v-model="showApproveForm">
                <approve-step-form @saved="hideApproveForm" @canceled="hideApproveForm"></approve-step-form>
            </modal-dialog>
        </div>

        <slot></slot>

        <slot name="log">
            <div class="mb-6">
                <h4 class="text-md font-bold mb-2">Step {{step}} Progress Log</h4>
                <application-log :step="step"></application-log>
            </div>
        </slot>
    </div>
</template>
<script>
import { mapGetters } from 'vuex'
import { formatDate } from '../../date_utils'
import ApplicationLog from './ApplicationLog'
import DocumentManager from './documents/DocumentManager'
import ApproveStepForm from './ApproveStepForm'

export default {
    components: {
        ApplicationLog,
        DocumentManager,
        ApproveStepForm,
    },
    props: {
        step: {
            type: Number,
            required: true
        },
        documentName: {
            type: String,
            required: false,
            default: 'Set a document-type attribute if you don\'t use the "document" slot'
        },
        documentType: {
            type: Number,
            required: false,
            default: 1
        },
        documentGetsReviewed: {
            type: Boolean,
            required: false,
            default: true
        },
        approveButtonLabel: {
            type: String,
            required: false,
            default: 'Set "approve-button-label" if not overriding slot "approve-button"'
        }
    },
    emits: ['documentUploaded', 'stepApproved'],
    data() {
        return {
            showApproveForm: false
        }
    },
    computed: {
        ...mapGetters({
            application: 'applications/currentItem'
        }),
        isCurrentStep () {
            return this.step == this.application.current_step
        },
        dateApproved () {
            if (this.application.approvalDateForStep(this.step)) {
                return formatDate(this.application.approvalDateForStep(this.step))
            }

            return null;
        }
    },
    methods: {
        startApproveStep () {
            this.showApproveForm = true;
        },
        approveStep () {
            this.$store.dispatch('applications/approveCurrentStep', {application: this.application, step: this.step})
        },
        hideApproveForm () {
            this.showApproveForm = false;
        }
    }
}
</script>