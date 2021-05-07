<template>
    <div>
        <h3 class="text-lg">Approve Step {{application.current_step}}</h3>
        
        <input-row v-model="dateApproved" type="date" :errors="errors.date_approved" label="Date Approved"></input-row>

        <dictionary-row label="">
            <label class="text-sm" v-if="[1,2].indexOf(application.current_step) > -1">
                <input type="checkbox" v-model="notifyContacts" :value="true"> Send notification email to contacts
            </label>
            <div class="text-gray-400 text-xs">
                No emails have been defined for step {{application.current_step}} approval.
            </div>
        </dictionary-row>

        <!-- <dictionary-row label="">
            <label class="text-sm">
                <input type="checkbox" v-model="notifyClingen"> Send notification to ClinGen system administrators
            </label>
        </dictionary-row> -->

        <button-row>
            <button class="btn" @click="cancel">Cancel</button>
            <button class="btn blue" @click="save">Approve step {{application.current_step}}</button>
        </button-row>
    </div>
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
    }
}
</script>