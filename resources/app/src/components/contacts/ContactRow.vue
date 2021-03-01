<style lang="postcss">
    .btn-delete {
       @apply cursor-pointer inline-block p-0.5 rounded-full border border-red-500 text-red-500 align-text-top outline-none;
    }
    .btn-delete:active, .btn-delete:focus {
        @apply outline-none;
    }
    .btn-delete:active{
        @apply border-red-900 text-red-900 bg-gray-100 outline-none;
        transform: scale(.95)
    }
</style>
<template>
    <div>
        {{contact.name}}
        -
        <a class="text-blue-500 underline" :href="`mailto:${contact.email}`">{{contact.email}}</a>
        -
        {{contact.phone}}
        
        <button class="btn-delete" @click="confirmRemove">
            <icon-close :height="8" :width="8"></icon-close>
        </button>

        <modal-dialog v-model="showRemoveConfirmation">
            <h4 class="text-lg mb-3 border-b pb-1">Confirm Contact Removal</h4>
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
import is_validation_error from '../../http/is_validation_error'
import IconClose from '../icons/IconClose'

export default {
    components: {
        IconClose
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
            application: 'applications/currentItem'
        })
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
                    alert(error.response.data.errors)
                    return;
                }
                throw error
            }
        }
    }
}
</script>