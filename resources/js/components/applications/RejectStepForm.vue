<script>
import {mapGetters} from 'vuex';
import {api} from '@/http';
import isValidationError from '@/http/is_validation_error';
import UserDefinedMailForm from '@/components/forms/UserDefinedMailForm.vue';

export default {
    components: {
        UserDefinedMailForm
    },
    emits: [
        'canceled',
        'saved'
    ],
    data() {
        return {
            notifyContacts: true,
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
        submissionText () {
            return `Request Revisions${this.notifyContacts ? ' and notify' : ''}`
        }
    },
    watch: {
        notifyContacts: {
            immediate: true,
            handler: function (to) {
                if (to) {
                    this.getEmailTemplate();
                }
            }
        }
    },
    methods: {
        clearForm() {
            this.dateApproved = null;
            this.notifyContacts = true;
        },
        cancel () {
            this.clearForm();
            this.$emit('canceled');
        },
        async save () {

            try {
                const data = {
                    notify_contacts: this.notifyContacts,
                    subject: this.email.subject,
                    body: this.email.body,
                    attachments: this.email.files
                };

                const url = `/api/groups/${this.group.uuid}/application/submission/${this.group.expert_panel.pendingSubmission.id}/rejection`;
                await api.post(url, data)
                    .then(rsp => rsp.data);


                this.clearForm();
                this.$emit('saved');

            } catch (e) {
                if (isValidationError(e)) {
                    this.errors = e.response.data.errors
                    return;
                }
                throw e;
            }
        },
        getEmailTemplate () {
            api.get(`/api/email-drafts/groups/${this.group.uuid}`,
                {params: {templateClass: 'App\\Mail\\UserDefinedMailTemplates\\ApplicationRevisionRequestTemplate'}})
                .then(response => {
                    this.email = response.data;
                    this.email.files = [];
                })

        }
    },
    mounted() {
    }

}
</script>
<template>
    <form-container>
        <dictionary-row label="">
            <div>
                <label class="text-sm">
                    <input type="checkbox" v-model="notifyContacts" :value="true">
                    <div>Send notification email to contacts</div>
                </label>
            </div>
        </dictionary-row>
        <static-alert
            v-if="!application.hasPendingSubmissionForCurrentStep"
            variant="warning"
        >
            The expert panel has not yet submitted the application for approval.
            <br>
            You can approve this application but be aware that it is not part of the "normal" application workflow.
        </static-alert>

        <transition name="slide-fade-down">
            <user-defined-mail-form v-model="email" v-show="notifyContacts"/>
        </transition>

        <button-row @canceled="cancel" @submitted="save" :submit-text="submissionText" />
    </form-container>
</template>
