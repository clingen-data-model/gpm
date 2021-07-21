<template>
    <div class="overflow-x-auto">
        <div class="mb-6">

            <div class="flex justify-between text-lg font-bold pb-2 mb-2 border-b">
                <h2>
                    {{title}}
                </h2>
                <div v-if="dateApproved">
                    <div class="flex space-x-1" v-if="!editApprovalDate">
                        <div class="text-white bg-green-600 rounded-xl px-2">
                            Appproved: {{dateApproved}}
                        </div>
                        <edit-button class="text-black" @click="initEditApprovalDate"></edit-button>
                    </div>
                    <div class="flex space-x-1" v-else>
                        <date-input v-model="newApprovalDate"></date-input>
                        <button class="btn blue" @click="updateApprovalDate">Save</button>
                        <remove-button @click="editApprovalDate = false"></remove-button>
                    </div>
                </div>
            </div>
            <slot name="document">
                <document-manager
                    :title="documentName"
                    class="border-b"
                    :application="application"
                    :document-type-id="documentType"
                    :getsReviewd="documentGetsReviewed"
                    :step="step"
                ></document-manager>
            </slot>
        </div>

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
            <modal-dialog v-model="showApproveForm" size="xl">
                <approve-step-form @saved="hideApproveForm" @canceled="hideApproveForm"></approve-step-form>
            </modal-dialog>
        </div>

        <slot></slot>

        <slot name="log">
            <div class="mb-6">
                <h3 class="mb-2">Step {{step}} Progress Log</h3>
                <application-log :step="step"></application-log>
            </div>
        </slot>
    </div>
</template>
<script>
import { mapGetters } from 'vuex'
import { formatDate } from '@/date_utils'
import ApplicationLog from '@/components/applications/ApplicationLog'
import DocumentManager from '@/components/applications/documents/DocumentManager'
import ApproveStepForm from '@/components/applications/ApproveStepForm'
import EditButton from '@/components/buttons/EditIconButton'
import RemoveButton from '@/components/buttons/RemoveButton'
import is_validation_error from '@/http/is_validation_error'

export default {
    components: {
        ApplicationLog,
        DocumentManager,
        ApproveStepForm,
        EditButton,
        RemoveButton
    },
    props: {
        title: {
            type: String, 
            required: false,
            default: 'YOU SHOULD SET A TITLE'
        },
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
            showApproveForm: false,
            editApprovalDate: false,
            newApprovalDate: null
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
        },
        initEditApprovalDate () {
            this.editApprovalDate = true;
            this.newApprovalDate = this.dateApproved;
        },
        async updateApprovalDate() {
            try {
                await this.$store.dispatch(
                    'applications/updateApprovalDate', 
                    {
                        application: this.application, 
                        dateApproved: this.newApprovalDate, 
                        step: this.step
                    }
                );
                this.editApprovalDate = false;
            } catch (error) {
                if (is_validation_error(error)) {
                    this.errors = error.response.data.errors;
                }
            }
        }
    }
}
</script>