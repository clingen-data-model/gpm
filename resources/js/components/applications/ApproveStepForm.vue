<script>
import {mapGetters} from 'vuex'
import isValidationError from '@/http/is_validation_error';
import ApprovalNotificationFields from './ApprovalNotificationFields.vue'

export default {
    components: {
        ApprovalNotificationFields
    },
    emits: [
        'canceled',
        'saved'
    ],
    data() {
        return {
            dateApproved: null,
            notifyContacts: false,
            emailPending: false,
            email: {
                subject: '',
                body: '',
                cc: [],
                to: [],
                files: []
            },
            errors: {}
        }
    },
    computed: {
        ...mapGetters({
            group: 'groups/currentItemOrNew'
        }),
        application () {
            return this.group.expert_panel;
        },
    },
    mounted() {
    },
    methods: {
        clearForm() {
            this.dateApproved = null;
            this.notifyContacts = false;
            this.email = { subject: '', body: '', cc: [], to: [], files: [] };
        },
        cancel () {
            this.clearForm();
            this.$emit('canceled');
        },
        async save () {
            if (this.emailPending) return;
            const data = {
                group: this.group, 
                dateApproved: this.dateApproved,
                notifyContacts: this.notifyContacts,
                subject: this.email.subject,
                body: this.email.body,
                attachments: this.email.files
            };

            try {
                await this.$store.dispatch('groups/approveCurrentStep', data)
                this.clearForm();
                this.$emit('saved');
            } catch (e) {
                if (isValidationError(e)) {
                    this.errors = e.response.data.errors
                }
            }
        }
    }

}
</script>
<template>
  <form-container>
    <h2>Approve Step {{ application.current_step }}</h2>

    <input-row v-model="dateApproved" type="date" :errors="errors.date_approved" label="Date Approved" />

    <ApprovalNotificationFields
      v-model:notify-contacts="notifyContacts"
      v-model:email="email"
      v-model:pending="emailPending"
      :group="group"
      :step="Number(application.current_step)"
    />
    <static-alert
      v-if="!application.hasPendingSubmissionForCurrentStep"
      variant="warning"
    >
      The expert panel has not yet submitted the application for approval.  
      <br>
      You can approve this application but be aware that it is not part of the "normal" application workflow.
    </static-alert>
        
    <button-row>
      <button class="btn" @click="cancel">
        Cancel
      </button>
      <button class="btn blue" :disabled="emailPending" @click="save">
        Approve step {{ application.current_step }}
        <span v-if="notifyContacts">
          and notify
        </span>
      </button>
    </button-row>
  </form-container>
</template>
