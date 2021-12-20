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
            <div v-show="notifyContacts">
                <h4 class="font-bold border-b">Email</h4>
                <dictionary-row label="To">
                    <static-alert v-if="!group.hasContacts" class="flex-1" variant="danger">
                        There are no contacts to notify!!
                    </static-alert>
                    <ul v-if="group.hasContacts">
                        <li v-for="contact in email.to" :key="contact.email">
                            <router-link 
                                :to="{name: 'PersonDetail', params: {uuid: contact.uuid}}"
                                class="text-blue-600 hover:underline" 
                                target="person"
                            >
                                {{contact.name}} &lt;{{contact.email}}&gt;</router-link>
                        </li>
                    </ul>
                </dictionary-row>
                <dictionary-row label="Cc">
                    <div v-if="email.cc.length > 0">
                        <truncate-expander :value="ccAddresses" :truncate-length="100"></truncate-expander>
                    </div>
                    <div class="text-gray-500" v-else>None</div>
                </dictionary-row>
                <input-row label="Subject">
                    <input type="text" v-model="email.subject" class="w-full">
                </input-row>
                <input-row label="Body">
                    <rich-text-editor  v-model="email.body"></rich-text-editor>
                </input-row>
                <input-row label="Attachments">
                    <input type="file" multiple ref="attachmentsField">
                </input-row>
                <note v-if="emailCced">ClinGen Services will be carbon copied on this email.</note>
            </div>
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

export default {
    components: {
        RichTextEditor
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
                to: []
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
        emailCced () {
            return this.application.current_step == 1 || this.application.current_step == 4
        },
        ccAddresses () {
            return this.email.cc.map(c => c.email).join(', ')
        }
    },
    watch: {
        notifyContacts: function (to) {
            if (to) {
                api.get(`/api/email-drafts/${this.application.uuid}/${this.application.current_step}`)
                    .then(response => {
                        this.email = response.data;
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
                application: this.application, 
                dateApproved: this.dateApproved,
                notifyContacts: this.notifyContacts,
                subject: this.email.subject,
                body: this.email.body,
                attachments: this.$refs.attachmentsField.files
            };

            try {
                await this.$store.dispatch('applications/approveCurrentStep', data)
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