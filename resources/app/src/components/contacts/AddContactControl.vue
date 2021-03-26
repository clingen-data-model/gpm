<template>
    <div>
        <div class="align-baseline">
            <button class="btn btn-xs" @click="showForm = true">
                <slot>Add Contact</slot>
            </button>
        </div>
        <modal-dialog v-model="showForm">
            <new-contact-form @done="showForm = false" @saved="triggerAddedContact"></new-contact-form>
        </modal-dialog>
    </div>
</template>
<script>
import {mapGetters} from 'vuex'
import NewContactForm from './NewContactForm'

export default {
    components: {
        NewContactForm
    },
    emits: [
        'contactadded'
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
    methods: {
        triggerAddedContact() {
            this.$emit('contactadded');
        }
    }
}
</script>