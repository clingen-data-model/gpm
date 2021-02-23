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
            <h4 class="text-lg">Add a contact</h4>
            <div>
                <input-row 
                    type="text" 
                    v-model="newContact.first_name" 
                    :errors="errors.first_name"
                    placeholder="Elenor" 
                    label="First name"
                ></input-row>
                <input-row 
                    type="text" 
                    v-model="newContact.last_name" 
                    :errors="errors.last_name"
                    placeholder="Shelstrop" 
                    label="Last name"
                ></input-row>
                <input-row 
                    type="text" 
                    v-model="newContact.email" 
                    :errors="errors.email"
                    placeholder="elenor@medplace.com" 
                    label="Email"
                ></input-row>
                <input-row 
                    type="text" 
                    v-model="newContact.phone" 
                    :errors="errors.phone"
                    placeholder="1-555-867-5309" 
                    label="Phone"
                ></input-row>
                <div class="btn-row">
                    <button class="btn" @click="cancel">Cancel</button>
                    <button class="btn blue" @click="save">Save</button>
                </div>
            </div>

        </modal-dialog>
    </div>
</template>
<script>
import {mapGetters} from 'vuex'
import is_validation_error from '../../http/is_validation_error'
import ContactRow from './ContactRow'

export default {
    components: {
        ContactRow
    },
    props: {
        
    },
    data() {
        return {
            newContact: {
                first_name: null,
                last_name: null,
                email: null,
                phone: null,
                role: null,
                uuid: null
            },
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
        clearForm () {
            this.newContact = {
                first_name: null,
                last_name: null,
                email: null,
                phone: null,
                role: null,
                uuid: null
            }
        },
        clearErrors () {
            this.errors = {};
        },
        cancel () {
            this.clearForm();
            this.$emit('canceled');
        },
        async save () {
            this.clearErrors();
            try {
                await this.$store.dispatch('addContact', {application: this.application, contactData: this.newContact})
                this.clearForm();
                this.showForm = false;
            } catch (error) {
                if (is_validation_error(error)) {
                    this.errors = error.response.data.errors
                }
            }
        }
    }
}
</script>