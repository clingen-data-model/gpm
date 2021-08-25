<template>
    <form-container>
        <h2>Approve Step {{application.current_step}}</h2>
        
        <input-row v-model="dateApproved" type="date" :errors="errors.date_approved" label="Date Approved"></input-row>

        <dictionary-row label="">
            <div>
                <label class="text-sm block">
                    <input type="checkbox" v-model="notifyContacts" :value="true"> Send notification email to contacts
                </label>
            </div>
        </dictionary-row>
        
        <transition name="slide-fade-down">
            <div v-show="notifyContacts">
                <h4 class="font-bold border-b">Email</h4>
                <dictionary-row label="To">
                    <static-alert v-if="application.contacts.length == 0" class="flex-1" variant="danger">
                        There are no contacts to notify!!
                    </static-alert>
                    <ul v-if="application.contacts.length > 0">
                        <li v-for="contact in application.contacts" :key="contact.id">
                            <router-link 
                                :to="{name: 'person-detail', params: {uuid: contact.uuid}}"
                                class="text-blue-600 hover:underline" 
                                :target="`person-${contact.id}`"
                            >
                                {{contact.name}} &lt;{{contact.email}}&gt;</router-link>
                        </li>
                    </ul>
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
                <note v-if="application.current_step == 1">ClinGen Services will be carbon copied on this email.</note>
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
            },
            errors: {}
        }
    },
    computed: {
        ...mapGetters({
            application: 'applications/currentItem'
        })
    },
    watch: {
        notifyContacts: function (to) {
            console.info('url:', `/api/email-drafts/${this.application.uuid}/${this.application.current_step}`)
            console.info('to', to)
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
        // console.log(this.$el.querySelectorAll('input'));
        // this.$el.querySelectorAll('input')[0].focus();
    }

}
</script>