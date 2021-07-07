<template>
    <div>
        <h2 class="border-b pb-1 mb-3">Mark Document Reviewed</h2>

        <input-row v-model="dateReviewed" label="Date Reviewed" :errors="errors.date_reviewed" type="date"></input-row>
        <button-row>
            <button class="btn" @click="cancel">Cancel</button>
            <button class="btn blue" @click="saveDateReviewed">Mark Reviewed</button>
        </button-row>
    </div>
</template>
<script>
import isValidationError from '../../../http/is_validation_error'

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
            isFinal: false,
            errors: {}
        }
    },
    computed: {
        isReviewed () {
            return Boolean(this.dateReviewed)
        }
    },
    methods: {
        async saveDateReviewed() {
            try {
                await this.$store.dispatch(
                        'applications/markDocumentReviewed',
                        {
                            application: this.application, 
                            document: this.document, 
                            dateReviewed: this.dateReviewed,
                            isFinal: this.isFinal
                        });
    
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