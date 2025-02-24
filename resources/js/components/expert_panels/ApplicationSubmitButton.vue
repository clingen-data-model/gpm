<script>
import RequirementsItem from '@/components/expert_panels/RequirementsItem.vue'
import {isValidationError} from '@/http'
import configs from '@/configs'

const {submissions} = configs;

export default {
    name: 'ApplicationSubmitButton',
    components: {
        RequirementsItem,
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
            bypassReqs: false
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
                    return 'VCEP Group Definition';
                case 4:
                    return 'VCEP Sustained Curation Plans'
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
        async confirmSubmission () {
            try {
                await this.$store.dispatch('groups/submitApplicationStep', {group: this.group, notes: this.notes});
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
            }
        },
        cancelSubmission () {
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
          <dev-component v-if="hasRole('super-user')">
            <button @click="bypassRequirements">
              Bypass Requirements
            </button>
          </dev-component>
        </div>
      </popover>
    </div>
    <teleport to="body">
      <transition name="fade">
        <modal-dialog v-model="showSubmissionConfirmation" title="Submit your application">
          <p class="text-lg">
            You are about to submit your {{ submissionName }} application.
          </p>
          <static-alert class="text-md" variant="info">
            Before submitting, please note:
            <ol class="list-decimal pl-6">
              <li>
                Typical response times are between one and two weeks.
              </li>
              <li>
                Questions, revisions, and other comments will be conveyed via email.
              </li>
              <li>
                Once submitted you will not be able to update your application until the submission has been processed.
              </li>
            </ol>
          </static-alert>
          <div class="mt-4 text-lg">
            Optional notes for reviewers:
          </div>
          <input-row label="" :errors="errors.notes" vertical>
            <textarea v-model="notes" rows="5" class="w-full" />
          </input-row>
          <button-row @submitted="confirmSubmission" @canceled="cancelSubmission" />
        </modal-dialog>
      </transition>
    </teleport>
  </div>
</template>
