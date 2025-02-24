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
    emits: [
      'sent',
      'canceled',
    ],
    data() {
        return {
            workingCopy: {},
            showBcc: false,
            showCc: false,
            errors: {}
        }
    },
    computed: {
        toErrors () {
            return this.assembleErrorsForAddressField('to')
        },
        ccErrors () {
            return this.assembleErrorsForAddressField('cc')
        },
        bccErrors () {
            return this.assembleErrorsForAddressField('bcc')
        }
    },
    watch: {
        mailData: {
            immediate: true,
            handler (to) {
                this.workingCopy = to || {};
                this.showCc = false;
                // eslint-disable-next-line eqeqeq
                if (this.workingCopy.cc !== null && this.workingCopy.cc != []) {
                    this.showCc = true;
                }
                this.showBcc = false;
                // eslint-disable-next-line eqeqeq
                if (this.workingCopy.bcc !== null && this.workingCopy.bcc != []) {
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
                this.errors = {};
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
        },
        assembleErrorsForAddressField (field) {
            let addressErrors = [];
            const pattern = new RegExp(`${field}.\\d+.address`);

            Object.keys(this.errors).filter(key => {
                    return key.match(pattern)
                })
                .forEach(key => {
                    addressErrors = [...addressErrors, ...this.errors[key]];
                });

            const set = new Set([
                ...(this.errors.to || []),
                ...addressErrors
            ]);
            return [...set];
        }

    }
}
</script>
<template>
  <div>
    <input-row label="To" :errors="toErrors">
      <recipient-input v-model="workingCopy.to" />
    </input-row>
    <input-row label="Cc" :errors="errors.ccErrors">
      <recipient-input v-show="showCc" v-model="workingCopy.cc" />

      <div v-if="workingCopy.cc && workingCopy.cc.length > 0 && !showCc">
        <truncate-expander :value="workingCopy.cc.map(i => i.address).join(', ')" :truncate-length="100" />
      </div>

      <button class="btn btn-xs" @click="showCc = !showCc">
        {{ showCc ? 'Hide Cc' : 'Show Cc' }}
      </button>
    </input-row>
    <input-row label="Bcc" :errors="bccErrors">
      <recipient-input v-show="showBcc" v-model="workingCopy.bcc" />
      <button class="btn btn-xs" @click="showBcc = !showBcc">
        {{ showBcc ? 'Hide Bcc' : 'Show Bcc' }}
      </button>
    </input-row>
    <input-row label="Subject" :errors="errors.subject">
      <input v-model="workingCopy.subject" type="text" class="w-full">
    </input-row>

    <input-row label="Body" :errors="errors.body">
      <rich-text-editor v-model="workingCopy.body" />
    </input-row>

    <input-row label="Attachments" :errors="errors.attachments">
      <input type="file" multiple>
      <note class="mt-2">
        Please note that if you are "Resending" an email, any attachments on the original email must be re-added.
      </note>
    </input-row>

    <button-row submit-text="Send" @submitted="sendMail" @cancel="$emit('canceled')" />
  </div>
</template>
