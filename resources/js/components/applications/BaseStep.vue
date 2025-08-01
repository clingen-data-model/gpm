<script>
import { mapGetters } from 'vuex'
import { formatDate } from '@/date_utils'
import ApplicationLog from '@/components/applications/ApplicationLog.vue'
import StepControls from '@/components/applications/StepControls.vue'
import RemoveButton from '@/components/buttons/RemoveButton.vue'
import is_validation_error from '@/http/is_validation_error'

export default {
    components: {
        ApplicationLog,
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
            return Number.parseInt(this.step) === Number.parseInt(this.application.current_step)
        },
        dateApproved () {
            if (this.application.approvalDateForStep(this.step)) {
                return formatDate(this.application.approvalDateForStep(this.step))
            }

            return null;
        },
    },
    methods: {
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
                }
            }
        }
    }
}
</script>

<template>
  <div class="overflow-x-auto">
    <div class="mb-6">
      <StepControls
        v-if="!application.stepIsApproved(step)"
        :step="step"
        @updated="handleUpdated"
      />
      <div class="flex justify-between text-lg font-bold pb-2 mb-2 border-b">
        <div class="flex space-x-2">
          <h2>
            {{ title }}
          </h2>
          <div v-if="dateApproved">
            <div v-if="!editApprovalDate" class="flex space-x-1">
              <div class="text-white bg-green-600 rounded-xl px-2">
                Approved: {{ dateApproved }}
              </div>
              <edit-icon-button class="text-black" @click="initEditApprovalDate" />
            </div>
            <div v-else class="flex space-x-1">
              <date-input v-model="newApprovalDate" />
              <button class="btn blue" @click="updateApprovalDate">
                Save
              </button>
              <RemoveButton @click="editApprovalDate = false" />
            </div>
          </div>
        </div>
        <div>
          <button class="btn btn-xs" @click="goToPrintable">
            Printable Application
          </button>
        </div>
      </div>

      <div class="screen-block-container">
        <slot name="sections">
          Step sections here!
        </slot>
      </div>
    </div>

    <div>
      <slot />
    </div>

    <slot name="log">
      <div class="mb-6 mt-4 border-t pt-4">
        <h3 class="mb-2">
          Step {{ step }} Progress Log
        </h3>
        <ApplicationLog :step="step" />
      </div>
    </slot>
  </div>
</template>
