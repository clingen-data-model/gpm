<template>
    <div>
        <dictionary-row label="Final Document">
            <a href="" class="text-blue-500 underline" v-if="finalDocument.filename">
                {{finalDocument.filename}}
            </a>
            <span v-else>none on file</span>
        </dictionary-row>
        <dictionary-row label="Date Received">
            {{firstDateReceived}}
        </dictionary-row>
        <dictionary-row label="Date Reviewed">
            {{finalDateReviewed}}
        </dictionary-row>
        <dictionary-row label="Date Approved">
            {{dateApproved}}
        </dictionary-row>
    </div>
</template>
<script>
import { formatDate } from '../../date_utils'

export default {
    props: {
        application: {
            type: Object,
            required: true
        },
        documentTypeId: {
            type: Number,
            required: true
        },
        step: {
            type: Number,
            required: false,
            default: null
        }        
    },
    data() {
        return {
            
        }
    },
    computed: {
        finalDocument() {
            return this.application.finalDocumentOfType(this.documentTypeId);
        },
        finalDateReviewed () {
            if (!this.finalDocument.date_reviewed) {
                return '--'
            }
            return formatDate(this.finalDocument.date_reviewed)
        },
        firstDateReceived () {
            if (!this.finalDocument.date_reviewed) {
                return '--'
            }
            return formatDate(this.application.firstDocumentOfType(this.documentTypeId).date_received)
        },
        dateApproved () {
            if (!this.finalDocument.date_reviewed) {
                return '--'
            }
            return formatDate(this.application.approvalDateForStep(this.step))
        }
    },
    methods: {

    }
}
</script>