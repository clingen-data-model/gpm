<template>
    <div>
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
    </div>
</template>
<script>
import {mapGetters} from 'vuex'
import is_validation_error from '../../http/is_validation_error'

export default {
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
            errors: {}
        }
    },
    emits: [
        'canceled',
        'saved',
        'done'
    ],
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
            this.$emit('done');
        },
        async save () {
            this.clearErrors();
            try {
                await this.$store.dispatch('addContact', {application: this.application, contactData: this.newContact});
                this.clearForm();
                this.$emit('saved');
                this.$emit('done');
            } catch (error) {
                if (is_validation_error(error)) {
                    this.errors = error.response.data.errors
                    return;
                }
                throw error
            }
        }
    }
}
</script>