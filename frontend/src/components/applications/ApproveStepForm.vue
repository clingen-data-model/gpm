<template>
    <div>
        <h3 class="text-lg">Approve Step {{application.current_step}}</h3>
        
        <input-row v-model="dateApproved" type="date" :errors="errors.date_approved" label="Date Approved"></input-row>

        <div class="btn-row">
            <button class="btn" @click="cancel">Cancel</button>
            <button class="btn blue" @click="save">Approve step {{application.current_step}}</button>
        </div>
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
            errors: {}
        }
    },
    computed: {
        ...mapGetters({
            application: 'currentItem'
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
            const data = {application: this.application, dateApproved: this.dateApproved};
            try {
                await this.$store.dispatch('approveCurrentStep', data)
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