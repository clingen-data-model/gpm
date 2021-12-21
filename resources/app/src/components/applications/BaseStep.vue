<template>
    <div class="overflow-x-auto">
        <div class="mb-6">

            <div class="flex justify-between text-lg font-bold pb-2 mb-2 border-b">
                <div class="flex space-x-2">
                    <h2>
                        {{title}}
                    </h2>
                    <div v-if="dateApproved">
                        <div class="flex space-x-1" v-if="!editApprovalDate">
                            <div class="text-white bg-green-600 rounded-xl px-2">
                                Appproved: {{dateApproved}}
                            </div>
                            <edit-icon-button class="text-black" @click="initEditApprovalDate"></edit-icon-button>
                        </div>
                        <div class="flex space-x-1" v-else>
                            <date-input v-model="newApprovalDate"></date-input>
                            <button class="btn blue" @click="updateApprovalDate">Save</button>
                            <remove-button @click="editApprovalDate = false"></remove-button>
                        </div>
                    </div>
                </div>
                <div>
                    <button class="btn btn-xs" @click="toggleDocuments">{{docsToggleText}}</button>
                    &nbsp;
                    <button class="btn btn-xs" @click="toggleSections">{{sectionsToggleText}}</button>
                </div>
            </div>
            <transition name="slide-fade-down">
                <div v-if="showDocuments">
                <slot name="document">
                    <div class="mt-4 p-4 border rounded-xl bg-gray-50">
                        <document-manager
                            :title="documentName"
                            :application="application"
                            :document-type-id="documentType"
                            :getsReviewd="documentGetsReviewed"
                            :step="step"
                        ></document-manager>
                    </div>
                </slot>
                <hr class="border-gray-200 border-4">
                </div>
            </transition>

            <transition name="slide-fade-down">
                <slot name="sections" v-if="showSections">
                    Step sections here!
                </slot>
            </transition>
        </div>

        <!-- Approve step -->
        <div  
            class="border border-l-0 border-r-0 py-4 mb-6" 
            v-if="!application.stepIsApproved(step)"
        >
            <static-alert 
                variant="info" 
                v-if="application.hasPendingSubmissionForCurrentStep"
                class="mb-4"
            >
                This step was submitted by <strong>{{application.pendingSubmission.submitter.name}}</strong> on 
                <strong>{{formatDate(application.pendingSubmission.created_at)}}</strong> with the following notes:
                <blockquote>
                    {{application.pendingSubmission.notes}}
                </blockquote>
            </static-alert>
            <button 
                class="btn btn-lg w-full" 
                @click="startApproveStep"
                :disabled="!isCurrentStep"
                :title="isCurrentStep ? 'Approve this step' : 'You can only approve the application\'s current step'"
            >
                {{approveButtonLabel}}
            </button>
            <teleport to="body">
                <modal-dialog v-model="showApproveForm" size="xl" @closed="$refs.approvestepform.clearForm()">
                    <approve-step-form  
                        ref="approvestepform" 
                        @saved="handleApproved" 
                        @canceled="hideApproveForm"
                    />
                </modal-dialog>
            </teleport>
        </div>

        <slot></slot>

        <slot name="log">
            <div class="mb-6 mt-4 border-t pt-4">
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
import RemoveButton from '@/components/buttons/RemoveButton'
import is_validation_error from '@/http/is_validation_error'

export default {
    components: {
        ApplicationLog,
        DocumentManager,
        ApproveStepForm,
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
            newApprovalDate: null,
            showDocuments: true,
            showSections: true,
        }
    },
    computed: {
        ...mapGetters({
            group: 'groups/currentItemOrNew'
        }),
        application () {
            return this.group.expert_panel;
        },
        isCurrentStep () {
            return this.step == this.application.current_step
        },
        dateApproved () {
            if (this.application.approvalDateForStep(this.step)) {
                return formatDate(this.application.approvalDateForStep(this.step))
            }

            return null;
        },
        docsToggleText () {
            return this.showDocuments ? 'Hide Documents' : 'Show Documents';
        },
        sectionsToggleText () {
            return this.showSections ? 'Hide Sections' : 'Show Sections';
        }
    },
    watch: {
        group: {
            immediate: true,
            handler (to) {
                if(!to.hasDocumentsOfType(this.documentType)) {
                    this.showDocuments = false;
                }
            }
        }
    },
    methods: {
        toggleDocuments () {
            this.showDocuments = !this.showDocuments;
        },
        toggleSections () {
            this.showSections = !this.showSections;
        },
        startApproveStep () {
            this.showApproveForm = true;
        },
        approveStep () {
            this.$store.dispatch('applications/approveCurrentStep', {application: this.application, step: this.step})
            this.$emit('stepApproved')
        },
        handleApproved () {
            this.hideApproveForm();
            this.$emit('stepApproved');
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