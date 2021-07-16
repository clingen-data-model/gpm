<template>
    <div class="mt-8">
        <slot name="title">
            <div class="md:flex justify-between">
                <h3>{{title}}</h3>
                <button class="btn mb-2 btn-sm" @click="showUploadForm = true">
                    Upload a new {{showVersion ? `version`: 'document'}}
                </button>
            </div>
        </slot>
        <div v-if="application.stepIsApproved(step) && showVersion">
            <final-document-view
                :application="application"
                :document-type-id="documentTypeId"
                :step="step"
            >
            </final-document-view>
        </div>

        <div v-else>
            <document-list 
                :application="application"
                :document-type-id="documentTypeId"
                :show-version="showVersion"
            ></document-list>
            
        </div>
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
    }
}
</script>