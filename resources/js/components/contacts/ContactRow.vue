<template>
    <div>
        {{contact.name}}
        -
        <a class="text-blue-500 underline" :href="`mailto:${contact.email}`">{{contact.email}}</a>
        <span v-if="contact.phone">
            - {{contact.phone}}
        </span>
        &nbsp;
        <RemoveButton size="xs" @click="confirmRemove"></RemoveButton>        

        <modal-dialog v-model="showRemoveConfirmation" title="Confirm Contact Removal">
            <p class="mb-2">
                You are about to remove <strong>{{contact.name}}</strong> as a contact for this application.
                Do you want to continue?
            </p>

            <small class="text-gray-500">NOTE: This will not delete the person's record in this system.</small>
            

            <ul class="bg-red-200 bg-text-900 border-red-900 p-2" v-if="errors">
                <li v-for="(errors, field) in errors" :key="field">
                    {{field}}: {{errors.join(', ')}}
                </li>
            </ul>

            <button-row>
                <div class="btn" @click="cancel">Cancel</div>
                <div class="btn blue" @click="save">Yes, remove contact</div>
            </button-row>
        </modal-dialog>
    </div>
</template>
<script>
import { mapGetters } from 'vuex'
import is_validation_error from '@/http/is_validation_error'
import RemoveButton from '@/components/buttons/RemoveButton.vue'

export default {
    components: {
        RemoveButton
    },
    props: {
        contact: {
            type: Object,
            required: true
        }
    },
    emits: [
        'saved',
        'canceled',
        'done',
        'deleted'
    ],
    data() {
        return {
            showRemoveConfirmation: false,
            errors: null
        }
    },
    computed: {
        ...mapGetters({
            group: 'groups/currentItemOrNew'
        }),
        application () {
            return this.group.expert_panel;
        }
    },
    methods: {
        confirmRemove () {
            this.showRemoveConfirmation = true
        },
        cancel () {
            this.showRemoveConfirmation = false;
            this.$emit('canceled');
            this.$emit('done');
        },
        async save () {
            try {
                await this.$store.dispatch('applications/removeContact', {application: this.application, contact: this.contact})
                this.showRemoveConfirmation = false;
                this.$emit('saved');
                this.$emit('deleted');
                this.$emit('done');
            } catch (error) {
                if (is_validation_error(error)) {
                    // eslint-disable-next-line no-alert
                    alert(error.response.data.errors)
                    return;
                }
                throw error
            }
        }
    }
}
</script>