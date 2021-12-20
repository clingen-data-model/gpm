<template>
    <div>
        <contact-row 
            v-for="contact in group.contacts" :key="contact.id"
            :contact="contact"
        ></contact-row>
        <add-contact-control @new-contact-initiated="propagateEvent($event)"></add-contact-control>
    </div>
</template>
<script>
import {mapGetters} from 'vuex'
import AddContactControl from '@/components/contacts/AddContactControl'
import ContactRow from '@/components/contacts/ContactRow'

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
            group: 'groups/currentItemOrNew'
        }),
        application () {
            return this.group.expert_panel;
        }
    },
    methods: {
        propagateEvent() {
            this.$emit('new-contact-initiated')
        }
    }
}
</script>