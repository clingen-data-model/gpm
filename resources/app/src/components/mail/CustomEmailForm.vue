<template>
    <div>
        <dictionary-row label="To">
            <ul>
                <li v-for="recipient in workingCopy.to" :key="recipient.address">
                    <span v-if="recipient.name">{{recipient.name}} - </span>{{recipient.address}}
                </li>
            </ul>
        </dictionary-row>
        <input-row label="Cc">
            <recipient-input v-model="workingCopy.cc" v-show="showCc" />

            <div v-if="workingCopy.cc && workingCopy.cc.length > 0 && !showCc">
                <truncate-expander :value="workingCopy.cc.map(i => i.address).join(', ')" :truncate-length="100"></truncate-expander>
            </div>

            <button class="btn btn-xs" @click="showCc = !showCc">
                {{showCc ? 'Hide Cc' : 'Show Cc'}}
            </button>
        </input-row>
        <input-row label="Bcc">
            <recipient-input v-model="workingCopy.bcc" v-show="showBcc" />
            <button class="btn btn-xs" @click="showBcc = !showBcc">
                {{showBcc ? 'Hide Bcc' : 'Show Bcc'}}
            </button>
        </input-row>
        <input-row label="Subject">
            <input type="text" v-model="workingCopy.subject" class="w-full">
        </input-row>
        
        <input-row label="Body">
            <rich-text-editor  v-model="workingCopy.body"></rich-text-editor>
        </input-row>

        <input-row label="Attachments">
            <input type="file" multiple ref="attachmentsField">
            <note class="mt-2">Please note that if you are "Resending" an email, any attachments on the original email must be re-added.</note>
        </input-row>

        <button-row submit-text="Send" @submitted="sendMail" @cancel="$emit('canceled')"></button-row>
    </div>
</template>
<script>
import { api, isValidationError } from '@/http';
export default {
    name: 'CustomEmailForm',
    props: {
        mailData: {
            type: Object,
            required: false
        }
    },
    data() {
        return {
            workingCopy: {},
            showBcc: false,
            showCc: false,
        }
    },
    watch: {
        mailData: {
            immediate: true,
            handler (to) {
                this.workingCopy = to ? to : {};
                this.showCc = false;
                if (this.workingCopy.cc !== null && this.workingCopy.cc !== []) {
                    this.showCc = true;
                }
                this.showBcc = false;
                if (this.workingCopy.bcc !== null && this.workingCopy.bcc !== []) {
                    this.showBcc = true;
                }
            }
        }
    },
    methods: {
        async sendMail () {
            try {
                this.cleanData()
                await api.post(`/api/mail`, this.workingCopy)
                this.$emit('sent');
            } catch(error) {
                if (isValidationError(error)) {
                    this.errors = error.response.data.errors
                    return;
                }
                throw error;
            }
        },
        cleanData () {
            this.workingCopy.to = this.workingCopy.to ? this.workingCopy.to.filter(i => i.address !== '') : null;
            this.workingCopy.cc = this.workingCopy.cc ? this.workingCopy.cc.filter(i => i.address !== '') : null;
            this.workingCopy.bcc = this.workingCopy.bcc ? this.workingCopy.bcc.filter(i => i.address !== '') : null;
            console.log(this.workingCopy);
        }
    }
}
</script>