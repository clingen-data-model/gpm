<script>
import RequirementsItem from '@/components/expert_panels/RequirementsItem.vue'
import {isValidationError} from '@/http'
import configs from '@/configs'
import DevComponent from '@/components/dev/DevComponent.vue'
import SubmissionConfirmationModal from '@/components/applications/SubmissionConfirmationModal.vue'

const {submissions} = configs;

export default {
    name: 'ApplicationSubmitButton',
    components: {
        RequirementsItem,
        DevComponent,
        SubmissionConfirmationModal
    },
    props: {
        disabled: {
            type: Boolean,
            required: false,
            default: false
        },
        sections: {
            type: Array,
            required: false,
            default: () => []
        },
        step: {
            type: Object,
            required: true
        }
    },
    emits: [
        'submitted',
        'canceled'
    ],
    data() {
        return {
            showSubmissionConfirmation: false,
            notes: null,
            errors: {},
            bypassReqs: false,
            submitting: false,
        }
    },
    computed: {
        group () {
            return this.$store.getters['groups/currentItemOrNew'];
        },
        meetsRequirements () {
            if (this.bypassReqs) {
                return true;
            }
            return this.step.meetsRequirements(this.group);
        },
        requirementsUnmet () {
            return !this.step.meetsRequirements(this.group);
        },
        evaledRequirements () {
            return this.step.evaluateRequirements(this.group);
        },
        submissionName () {
            if (this.group.is_gcep) {
                return 'GCEP'
            }
            switch (this.group.expert_panel.current_step) {
                case 1:
                    return this.group.type.display_name + ' Group Definition';
                case 4:
                    return this.group.type.display_name + ' Sustained Curation Plans'
                default:
                    // alert('Specifications approval is handled in the CSpec registry.  Please go there to develope your AMCG/AMP Guideline Specifications.')
                    break;
            }
            return submissions.types.applications
        }
    },
    methods: {
        bypassRequirements () {
            this.bypassReqs = true;
        },
        initSubmission () {
            this.showSubmissionConfirmation = true;
            this.notes = null;
        },
        async confirmSubmission (notes) {
            if (this.submitting) { return; }
            this.submitting = true;
            try {
                await this.$store.dispatch('groups/submitApplicationStep', {group: this.group, notes});
                this.$store.commit('pushSuccess', 'Your application has been submitted for approval.')
                this.showSubmissionConfirmation = false;
                this.$emit('submitted');
            } catch (error) {
                if (isValidationError(error)) {
                    const errors = error.response.data.errors;

                    this.$store.commit('pushError', errors.group.join(','));
                    return;
                }

                throw error;
            } finally {
                this.submitting = false;
            }
        },
        cancelSubmission () {
            if (this.submitting) { return; }
            this.showSubmissionConfirmation = false;
            this.notes = null;
            this.$emit('canceled');
        },
    }
}
</script>
<template>
  <div>
    <div v-if="!group.expert_panel.hasPendingSubmission" class="p-4">
      <popover hover arrow>
        <template #content>
          <div>
            <RequirementsItem v-for="(req, idx) in evaledRequirements" :key="idx" :requirement="req" />
          </div>
        </template>
        <div>
          <div class="relative">
            <button class="btn btn-xl" @click="initSubmission">
              Submit for Approval
            </button>
            <!-- Add mask above button if requirements are unmet b/c vue3-popover doesn't respond to disabled components. -->
            <div v-if="!meetsRequirements" class="bg-white opacity-50 absolute top-0 bottom-0 left-0 right-0" />
          </div>
          <DevComponent v-if="hasRole('super-user')">
            <button @click="bypassRequirements">
              Bypass Requirements
            </button>
          </DevComponent>
        </div>
      </popover>
    </div>
    <SubmissionConfirmationModal
      v-model="showSubmissionConfirmation"
      title="Submit your application"
      :submission-name="`${submissionName} application`"
      notes-label="Required notes for reviewers:"
      submit-text="Submit"
      :submitting="submitting"
      @submitted="confirmSubmission"
      @canceled="cancelSubmission"
    />
  </div>
</template>
