<template>
    <div>
        <button class="btn mb-2" @click="showModal = true">Upload a new version</button>
        
        <data-table :fields="filteredFields" :data="filteredDocuments" :sort="{field: fields[0], desc: true}">
            <template v-slot:date_reviewed="item">
                <pre>{{item}}</pre>
            </template>
        </data-table>

        <modal-dialog v-model="showModal" @closed="$refs.uploadform.clearForm()">
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
    </div>
</template>
<script>
import {formatDate} from '../../date_utils'
import DocumentUploadForm from './DocumentUploadForm'

export default {
    name: 'DocumentManager',
    components: {
        DocumentUploadForm
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
            showModal: false,
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
                    type: String,
                    resolveValue (item) {
                        if (item.date_received) {
                            return formatDate(new Date(Date.parse(item.date_received)))
                        }

                        return null
                    }
                },
                {
                    name: 'date_reviewed',
                    type: Date,
                    resolveValue (item) {
                        if (item.date_reviewed) {
                            return formatDate(new Date(Date.parse(item.date_reviewed)))
                        }
                        return null;
                    }
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
        closeDialog(){
            this.showModal = false;
        }
    }
}
</script>