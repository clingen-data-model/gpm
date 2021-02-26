<template>
    <div>
        <button class="btn mb-2" 
            @click="showUploadForm = true"
        >
            Upload a new version
        </button>
        
        <data-table 
            :fields="filteredFields" 
            :data="filteredDocuments" 
            :sort="{field: fields[0], desc: true}"
            v-if="filteredDocuments.length > 0"
        >
            <template v-slot:cell-date_reviewed="item">
                <div class="text-center">
                    <span v-if="item.value">{{item.value}}</span>
                    <button v-else class="btn btn-xs" @click="showMarkReviewed(item.item)">Mark reviewed</button>
                </div>
            </template>
        </data-table>

        <modal-dialog v-model="showUploadForm" @closed="$refs.uploadform.clearForm()">
            <document-upload-form 
                :application="application" 
                :document-type-id="documentTypeId" 
                :step="step"
                @canceled="closeDialog" 
                @saved="closeDialog" 
                ref="uploadform"
            >
            </document-upload-form>
        </modal-dialog>

        <modal-dialog v-model="showReviewedForm">
            <document-reviewed-form :document="activeDocument" :application="application" @canceled="hideReviewedForm" @saved="hideReviewedForm"></document-reviewed-form>
        </modal-dialog>
    </div>
</template>
<script>
import DocumentUploadForm from './DocumentUploadForm'
import DocumentReviewedForm from './DocumentReviewedForm'

export default {
    name: 'DocumentManager',
    components: {
        DocumentUploadForm,
        DocumentReviewedForm
    },
    props: {
        application: {
            type: Object,
            required: true
        },
        documentTypeId: {
            type: Number,
            required: true
        },
        getsReviewed: {
            type: Boolean,
            required: false,
            default: true
        },
        step: {
            type: Number,
            required: false,
            default: null
        }
    },
    data() {
        return {
            showUploadForm: false,
            showReviewedForm: false,
            activeDocument: {},
            fields: [
                {
                    name: 'version',
                    type: Number
                },
                {
                    name: 'filename',
                    type: String
                },
                {
                    name: 'date_received',
                    type: Date,
                },
                {
                    name: 'date_reviewed',
                    type: Date,
                }
            ],
        }
    },
    computed: {
        filteredFields () {
            let clonedFields = [...this.fields]
            if (!this.getsReviewed) {
                const idx = clonedFields.findIndex(f => f && f.name == 'date_reviewed')
                clonedFields = clonedFields.slice(0,idx)
            }
            return clonedFields
        },
        filteredDocuments() {
            if (this.application && this.application.documents) {
                // return this.application.documents
                return this.application.documents.filter(d =>  d.document_category_id == this.documentTypeId)
            }
            return [];
        }
    },
    methods: {
        showMarkReviewed (item) {
            this.activeDocument = item;
            this.showReviewedForm = true;
        },
        hideReviewedForm () {
            this.showReviewedForm = false;
        },
        closeDialog(){
            this.showUploadForm = false;
        }
    }
}
</script>