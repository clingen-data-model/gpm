<script>
import {mapGetters} from 'vuex';
import ApproveStepForm from '@/components/applications/ApproveStepForm.vue'
import RejectStepForm from '@/components/applications/RejectStepForm.vue'

export default {
    name: 'StepApprovalControl',
    components: {
        ApproveStepForm,
        RejectStepForm,
    },
    props: {
        step: {
            type: Number,
            required: true
        },
        approveLabel: {
            type: String,
            required: false,
            default: 'Approve'
        }
    },
    data() {
        return {
            showApproveForm: false,
            showRejectForm: false,
            
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
                return this.formatDate(this.application.approvalDateForStep(this.step))
            }

            return null;
        },
    },
    methods: {
        startApproveStep () {
            this.showApproveForm = true;
        },
        startRejectSubmission () {
            this.showRejectForm = true;
        },
        approveStep () {
            this.$store.dispatch('applications/approveCurrentStep', {application: this.application, step: this.step})
            this.$emit('stepApproved')
            this.$emit('updated')
        },
        handleApproved () {
            this.hideApproveForm();
            this.$emit('stepApproved');
            this.$emit('updated');
        },
        hideApproveForm () {
            this.showApproveForm = false;
        },
        handleRejected () {
            this.hideRejectForm();
            this.$emit('stepRejected');
            this.$emit('updated');
        },
        hideRejectForm () {
            this.showRejectForm = false;
        },
    }
}
</script>

<template>
    <div  
        class="border border-l-0 border-r-0 py-4 mb-6" 
        v-if="!application.stepIsApproved(step)"
    >
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
            <div class="flex w-full space-x-4">
                <button 
                    class="btn btn-lg flex-1" 
                    @click="startApproveStep"
                    :disabled="!isCurrentStep"
                    :title="isCurrentStep ? 'Approve this step' : 'You can only approve the application\'s current step'"
                >
                    {{approveLabel}}
                </button>
                <button 
                    v-if="application.hasPendingSubmissionForCurrentStep"
                    class="btn btn-lg flex-1" 
                    @click="startRejectSubmission"
                >
                    Request revisions
                </button>
            </div>
        </div>


        <teleport to="body">
            <modal-dialog v-model="showApproveForm" size="xl" @closed="$refs.approvestepform.clearForm()">
                <approve-step-form  
                    ref="approvestepform" 
                    @saved="handleApproved" 
                    @canceled="hideApproveForm"
                />
            </modal-dialog>
            <modal-dialog 
                title="Request Revisions to Application"
                v-model="showRejectForm" 
                size="xl" 
                @closed="$refs.rejectsubmissionform.clearForm()"
            >
                <reject-step-form
                    ref="rejectsubmissionform"
                    @saved="handleRejected"
                    @canceled="hideRejectForm"
                />
            </modal-dialog>
        </teleport>
    </div>
</template>
