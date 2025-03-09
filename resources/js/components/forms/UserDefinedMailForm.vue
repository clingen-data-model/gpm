<script>
export default {
    name: 'ComponentName',
    props: {
        modelValue: {
            type: Object,
            default: () => ({})
        }
    },
    data() {
        return {

        }
    },
    computed: {
        group () {
            return this.$store.getters['groups/currentItemOrNew'];
        },
        workingEmail: {
            get () {
                return this.modelValue;
            },
            set (value) {
                this.emitEmailUpdate(value);
            }
        },
        application () {
            return this.group.expert_panel;
        },
        emailCced () {
            return this.application.current_step === 1 || this.application.current_step === 4
        },
        ccAddresses () {
            return this.modelValue.cc.map(c => c.address).join(', ')
        }
    },
    methods: {
        handleAttachments() {
            this.emitEmailUpdate(this.workingEmail);
        },
        emitEmailUpdate (data) {
            const emailData = {...data};
            Array.from(this.$refs.attachmentsField.files).forEach(file => {
                emailData.files.push(file);
            });
            this.$emit('update:email', data);
        }
    }
}
</script>
<template>
    <div>
        <h4 class="font-bold border-b">Email</h4>
        <dictionary-row label="To" label-class="w-36">
            <static-alert v-if="!group.hasContacts" class="flex-1" variant="danger">
                There are no contacts to notify!!
            </static-alert>
            <ul v-if="group.hasContacts">
                <li v-for="contact in workingEmail.to" :key="contact.email">
                    <router-link
                        :to="{name: 'PersonDetail', params: {uuid: contact.uuid}}"
                        class="text-blue-600 hover:underline"
                        target="person"
                    >
                        {{ contact.name }} &lt;{{ contact.email }}&gt;</router-link>
                </li>
            </ul>
        </dictionary-row>
        <dictionary-row label="Cc" label-class="w-36">
            <div v-if="workingEmail.cc.length > 0">
                <truncate-expander :value="ccAddresses" :truncate-length="100" />
            </div>
            <div class="text-gray-500" v-else>None</div>
        </dictionary-row>
        <input-row label="Subject">
            <input type="text" v-model="workingEmail.subject" class="w-full">
        </input-row>
        <input-row label="Body">
            <rich-text-editor v-model="workingEmail.body"></rich-text-editor>
        </input-row>
        <input-row label="Attachments">
            <input type="file" multiple ref="attachmentsField" @change="handleAttachments">
        </input-row>
        <note v-if="emailCced">ClinGen Services will be carbon copied on this email.</note>
    </div>

</template>
