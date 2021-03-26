<template>
    <div>
        <contact-row 
            v-for="contact in application.contacts" :key="contact.id"
            :contact="contact"
        ></contact-row>
        <add-contact-control></add-contact-control>
    </div>
</template>
<script>
import {mapGetters} from 'vuex'
import AddContactControl from './AddContactControl.vue'
import ContactRow from './ContactRow'
import NewContactForm from './NewContactForm'

export default {
    components: {
        ContactRow,
        NewContactForm,
        AddContactControl
    },
    props: {
        
    },
    data() {
        return {
        }
    },
    computed: {
        ...mapGetters({
            application: 'applications/currentItem'
        })
    },
    methods: {
    }
}
</script>