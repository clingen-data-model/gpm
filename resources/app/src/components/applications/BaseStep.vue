<script>
import { mapGetters } from 'vuex'
import { formatDate } from '@/date_utils'
import ApplicationLog from '@/components/applications/ApplicationLog.vue'
import DocumentManager from '@/components/applications/documents/DocumentManager.vue'
import StepControls from '@/components/applications/StepControls.vue'
import RemoveButton from '@/components/buttons/RemoveButton.vue'
import is_validation_error from '@/http/is_validation_error'

export default {
    components: {
        ApplicationLog,
        DocumentManager,
        StepControls,
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
    emits: ['documentUploaded', 'approved', 'updated'],
    data() {
        return {
            showApproveForm: false,
            editApprovalDate: false,
            newApprovalDate: null,
            showDocuments: true,
            showSections: true,
            documentsToggled: false,
            sectionsToggled: false,
            showRejectForm: false
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
                if(!to.hasDocumentsOfType(this.documentType) && !this.documentsToggled) {
                    this.showDocuments = false;
                }
            }
        },
    },
    methods: {
        toggleDocuments () {
            this.documentsToggled = true;
            this.showDocuments = !this.showDocuments;
        },
        toggleSections () {
            this.sectionsToggled = true;
            this.showSections = !this.showSections;
        },
        goToPrintable () {
            window.open(`/groups/${this.group.uuid}/application/review`);
        },

        handleUpdated () {
            this.$emit('updated');
        },

        initEditApprovalDate () {
            this.editApprovalDate = true;
            this.newApprovalDate = this.dateApproved;
        },
        async updateApprovalDate() {
            try {
                await this.$store.dispatch(
                    'groups/updateApprovalDate',
                    {
                        group: this.group,
                        dateApproved: this.newApprovalDate,
                        step: this.step
                    }
                );
                // this.group.expert_panel.updateApprovalDate(this.newApprovalDate, this.step);
                this.editApprovalDate = false;
            } catch (error) {
                if (is_validation_error(error)) {
                    this.errors = error.response.data.errors;
                    return;
                }
            }
        }
    }
}
</script>

<template>
    <div class="overflow-x-auto">
        <div class="mb-6">
            <step-controls v-if="!application.stepIsApproved(step)"
                :step="step"
                @updated="handleUpdated"
            />
            <div class="flex justify-between text-lg font-bold pb-2 mb-2 border-b">
                <div class="flex space-x-2">
                    <h2>
                        {{title}}
                    </h2>
                    <div v-if="dateApproved">
                        <div class="flex space-x-1" v-if="!editApprovalDate">
                            <div class="text-white bg-green-600 rounded-xl px-2">
                                Approved: {{dateApproved}}
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
                    &nbsp;
                    <button class="btn btn-xs" @click="goToPrintable">Printable Application</button>

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
                                @updated="$emit('updated')"
                            />
                        </div>
                    </slot>
                    <hr class="border-gray-200 border-4">
                </div>
            </transition>

            <transition name="slide-fade-down">
                <div class="screen-block-container">
                    <slot name="sections" v-if="showSections">
                        Step sections here!
                    </slot>
                </div>
            </transition>
        </div>

        <div>
            <slot></slot>
        </div>

        <slot name="log">
            <div class="mb-6 mt-4 border-t pt-4">
                <h3 class="mb-2">Step {{step}} Progress Log</h3>
                <application-log :step="step"></application-log>
            </div>
        </slot>
    </div>
</template>
