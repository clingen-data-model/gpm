<template>
    <div>
        <contact-row 
            v-for="contact in application.contacts" :key="contact.id"
            :contact="contact"
        ></contact-row>
        <div class="align-baseline">
            <button class="btn btn-xs" @click="showForm = true">
                Add Contact
            </button>
        </div>
        <modal-dialog v-model="showForm">
            <new-contact-form @done="showForm = false"></new-contact-form>
        </modal-dialog>
    </div>
</template>
<script>
import {mapGetters} from 'vuex'
import ContactRow from './ContactRow'
import NewContactForm from './NewContactForm'

export default {
    components: {
        ContactRow,
        NewContactForm
    },
    props: {
        
    },
    data() {
        return {
            showForm: false,
            errors: {}
        }
    },
    computed: {
        ...mapGetters({
            application: 'currentItem'
        })
    },
    methods: {
    }
}
</script>