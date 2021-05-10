<template>
    <div>
        <contact-row 
            v-for="contact in application.contacts" :key="contact.id"
            :contact="contact"
        ></contact-row>
        <add-contact-control @new-contact-initiated="propagateEvent($event)"></add-contact-control>
    </div>
</template>
<script>
import {mapGetters} from 'vuex'
import AddContactControl from './AddContactControl.vue'
import ContactRow from './ContactRow'

export default {
    components: {
        ContactRow,
        AddContactControl
    },
    props: {
        
    },
    emits: [
        'new-contact-initiated'
    ],
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
        propagateEvent() {
            this.$emit('new-contact-initiated')
        }
    }
}
</script>