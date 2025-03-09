<script>
import DocumentList from './DocumentList'
import DocumentUploadForm from './DocumentUploadForm'
import FinalDocumentView from './FinalDocumentView'

export default {
    name: 'DocumentManager',
    components: {
        DocumentUploadForm,
        FinalDocumentView,
        DocumentList
    },
    emits: ['updated'],
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
        },
        showVersion: {
            type: Boolean,
            default: true
        },
        title: {
            type: String, 
            required: false
        }
    },
    data() {
        return {
            showUploadForm: false,
        }
    },
    methods: {
        handleSaved () {
            this.showUploadForm = false;
            this.$emit('updated');
        }
    }
}
</script>
<template>
    <div>
        <slot name="title">
            <div class="md:flex justify-between">
                <h3>{{ title }} Documents</h3>
            </div>
        </slot>
        <div v-if="application.stepIsApproved(step) && showVersion">
            <FinalDocumentView
                :application="application"
                :document-type-id="documentTypeId"
                :step="step"
            >
            </FinalDocumentView>
        </div>

        <div v-else>
            <DocumentList 
                :application="application"
                :document-type-id="documentTypeId"
                :show-version="showVersion"
                @updated="$emit('updated')"
            ></DocumentList>
            
            <button class="btn mb-2 btn-sm" @click="showUploadForm = true">
                Upload a new {{ showVersion ? `version`: 'document' }}
            </button>
        </div>
        <modal-dialog v-model="showUploadForm" @closed="$refs.uploadform.clearForm()">
            <DocumentUploadForm 
                :application="application" 
                :document-type-id="documentTypeId" 
                :step="step"
                @canceled="showUploadForm = false" 
                @saved="handleSaved" 
                ref="uploadform"
            >
            </DocumentUploadForm>
        </modal-dialog>
    </div>
</template>