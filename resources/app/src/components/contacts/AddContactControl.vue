<template>
    <div>
        <div class="align-baseline">
            <button class="btn btn-xs" @click="initiateAddContact">
                <slot>Add Contact</slot>
            </button>
        </div>
        <modal-dialog v-model="showForm">
            <new-contact-form 
                ref="newContactForm"
                @done="showForm = false;"
                @saved="triggerAddedContact"
                @new-contact-canceled="$emit('new-contact-canceled')"
            ></new-contact-form>
        </modal-dialog>
    </div>
</template>
<script>
import {mapGetters} from 'vuex'
import NewContactForm from '@/components/contacts/NewContactForm'

export default {
    name: 'AddContactControl',
    components: {
        NewContactForm
    },
    emits: [
        'new-contact-initiated',
        'contact-added',
        'new-contact-canceled'
    ],
    data() {
        return {
            showForm: false,
            errors: {}        
        }
    },
    computed: {
        ...mapGetters({
            application: 'applications/currentItem'
        })
    },
    watch: {
        showForm: function () {
            if (!this.showForm) {
                this.$refs.newContactForm.clearForm();
            }
        }
    },
    methods: {
        initiateAddContact() {
            this.showForm = true;
            this.$emit('new-contact-initiated');
        },
        triggerAddedContact() {
            this.$emit('contact-added');
        }
    }
}
</script>