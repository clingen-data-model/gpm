<template>
    <div>
        <final-document-view
            :application="application"
            :document-type-id="documentTypeId"
            :step="step"
            v-if="application.stepIsApproved(step)"
        >
        </final-document-view>

        <div v-else>
            <button class="btn mb-2" @click="showUploadForm = true">
                Upload a new version
            </button>
            
            <document-list 
                :application="application"
                :document-type-id="documentTypeId" 
            ></document-list>
            
            <modal-dialog v-model="showUploadForm" @closed="$refs.uploadform.clearForm()">
                <document-upload-form 
                    :application="application" 
                    :document-type-id="documentTypeId" 
                    :step="step"
                    @canceled="showUploadForm = false" 
                    @saved="showUploadForm = false" 
                    ref="uploadform"
                >
                </document-upload-form>
            </modal-dialog>
        </div>
    </div>
</template>
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
        }
    }
}
</script>