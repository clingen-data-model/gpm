<template>
    <form-container>
        <h2>Approve Step {{application.current_step}}</h2>

        <input-row v-model="dateApproved" type="date" :errors="errors.date_approved" label="Date Approved"></input-row>

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

        <button-row>
            <button class="btn" @click="cancel">Cancel</button>
            <button class="btn blue" @click="save">
                Approve step {{application.current_step}}
                <span v-if="notifyContacts">
                    and notify
                </span>
            </button>
        </button-row>
    </form-container>
</template>
<script>
import {mapGetters} from 'vuex'
import api from '@/http/api';
import isValidationError from '@/http/is_validation_error';
import RichTextEditor from '@/components/forms/RichTextEditor.vue';
import UserDefinedMailForm from '@/components/forms/UserDefinedMailForm'

export default {
    components: {
        RichTextEditor,
        UserDefinedMailForm
    },
    emits: [
        'canceled',
        'saved'
    ],
    data() {
        return {
            dateApproved: null,
            notifyContacts: false,
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
    watch: {
        notifyContacts: function (to) {
            if (to) {
                api.get(`/api/email-drafts/${this.application.uuid}/${this.application.current_step}`)
                    .then(response => {
                        this.email = response.data;
                        this.email['files'] = [];
                    })
            }
        }
    },
    methods: {
        clearForm() {
            this.dateApproved = null;
            this.notifyContacts = false;
        },
        cancel () {
            this.clearForm();
            this.$emit('canceled');
        },
        async save () {
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
                    return;
                }
            }
        }
    },
    mounted() {
    }

}
</script>