<template>
    <div>
        <h3 class="text-lg border-b pb-1 mb-3">Mark Document Reviewed</h3>

        <input-row v-model="dateReviewed" label="Date Reviewed" :errors="errors.date_reviewed" type="date"></input-row>
        <div class="btn-row">
            <button class="btn" @click="cancel">Cancel</button>
            <button class="btn blue" @click="saveDateReviewed">Mark Reviewed</button>
        </div>
    </div>
</template>
<script>
import isValidationError from '../../http/is_validation_error'

export default {
    name: 'DocumentReviewForm',
    props: {
        application: {
            type: Object,
            required: true
        },
        document: {
            type: Object,
            required: true
        }
    },
    data() {
        return {
            dateReviewed: null,
            errors: {}
        }
    },
    computed: {
    },
    methods: {
        async saveDateReviewed() {
            try {
                await this.$store.dispatch('markDocumentReviewed',{application: this.application, document: this.document, dateReviewed: this.dateReviewed});
    
                this.clearForm();
                this.$emit('saved');
            } catch (e) {
                if (isValidationError(e)) {
                    this.errors = e.response.data.errors
                    return;
                }
            }
        },
        cancel () {
            this.clearForm();
            this.$emit('canceled');
        },
        clearForm() {
            this.dateReviewed = null;
        }
    }
}
</script>