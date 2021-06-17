<template>
    <form-container @keyup.enter="save">
        <h2>Approve Step {{application.current_step}}</h2>
        
        <input-row v-model="dateApproved" type="date" :errors="errors.date_approved" label="Date Approved"></input-row>

        <dictionary-row label="">
            <div>
                <label class="text-sm block">
                    <input type="checkbox" v-model="notifyContacts" :value="true"> Send notification email to contacts
                </label>
            </div>
        </dictionary-row>

        <button-row>
            <button class="btn" @click="cancel">Cancel</button>
            <button class="btn blue" @click="save">Approve step {{application.current_step}}</button>
        </button-row>
    </form-container>
</template>
<script>
import {mapGetters} from 'vuex'
import isValidationError from '../../http/is_validation_error';

export default {
    props: {
        
    },
    emits: [
        'canceled',
        'saved'
    ],
    data() {
        return {
            dateApproved: null,
            notifyContacts: false,
            notifyClinGen: false,
            errors: {}
        }
    },
    computed: {
        ...mapGetters({
            application: 'applications/currentItem'
        })
    },
    methods: {
        clearForm() {
            this.dateApproved = null;
        },
        cancel () {
            this.clearForm();
            this.$emit('canceled');
        },
        async save () {
            const data = {
                application: this.application, 
                dateApproved: this.dateApproved,
                notifyContacts: this.notifyContacts,
                notifyClingen: this.notifyClinGen,
            };

            try {
                await this.$store.dispatch('applications/approveCurrentStep', data)
                this.clearForm();
                this.$emit('saved');
            } catch (e) {
                if (isValidationError(e)) {
                    this.errors = e.response.data.errors
                    return;
                }
            }
        }
    },
    mounted() {
        // console.log(this.$el.querySelectorAll('input'));
        // this.$el.querySelectorAll('input')[0].focus();
    }

}
</script>