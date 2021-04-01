<template>
    <div>
        <data-table 
            :fields="filteredFields" 
            :data="filteredDocuments" 
            :sort="{field: fields[0].name, desc: true}"
            v-if="filteredDocuments.length > 0"
        >
            <template v-slot:cell-date_reviewed="{item, value}">
                <div class="text-center">
                    <span v-if="value">{{value}}</span>
                    <button v-else class="btn btn-xs" @click="showMarkReviewed(item)">Mark reviewed</button>
                </div>
            </template>

            <template v-slot:cell-is_final="{item}">
                <icon-checkmark 
                    v-if="!item.is_final" 
                    @click="markFinal(item)" 
                    title="Mark this the final version."
                    class="text-gray-300 cursor-pointer inline"
                ></icon-checkmark>
                <icon-checkmark 
                    class="text-green-600 inline" 
                    v-if="item.is_final"
                    title="This is the final document"
                ></icon-checkmark>
            </template>

            <template v-slot:cell-id="{item}">
                <div class="flex space-x-1">

                <button class="btn btn-xs" @click="downloadDocument(item)">
                    <icon-download width="12" height="16"></icon-download>
                </button>
                </div>
            </template>
        </data-table>

        <modal-dialog v-model="showReviewedForm">
            <document-reviewed-form 
                :document="activeDocument" 
                :application="application" 
                @canceled="hideReviewedForm" 
                @saved="hideReviewedForm"
            ></document-reviewed-form>
        </modal-dialog>
    </div>
</template>
<script>
import IconDownload from '../../icons/IconDownload'
import IconCheckmark from '../../icons/IconCheckmark'
import DocumentReviewedForm from './DocumentReviewedForm'

export default {
    components: {
        IconDownload,
        IconCheckmark,
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
    },
    data() {
        return {
            showReviewedForm: false,
            activeDocument: {},
            fields: [
                {
                    name: 'version',
                    label: 'Version',
                    type: Number
                },
                {
                    name: 'filename',
                    label: 'File',
                    type: String
                },
                {
                    name: 'date_received',
                    label: 'Date Received',
                    type: Date,
                },
                {
                    name: 'date_reviewed',
                    label: 'Date Reviewed',
                    type: Date,
                },
                {
                    name: 'is_final',
                    label: 'Final',
                    type: Boolean,
                    sortable: false,
                    class: ['text-center'],
                    headerClass: ['text-center']
                },
                {
                    name: 'id',
                    label: '',
                    sortable: false
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
                return this.application.documents.filter(d =>  d.document_type_id == this.documentTypeId)
            }
            return [];
        },
        hasFinalVersion() {
            return (this.filteredDocuments.findIndex(d => d.is_final) > -1);
        },
        finalVersion() {
            return this.filteredDocuments.find(d => d.is_final) || {};
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
        downloadDocument(item) {
            window.location = item.download_url;
        },
        confirmNewFinal(item) {
                const confirmMessage = `There is already a version of this document marked as final.  Only one version can be marked as final.  Do you want to mark this version (version ${item.version}) as final instead of ${this.finalVersion.version}`
                return confirm(confirmMessage);
        },
        markFinal(item) {
            if (!this.hasFinalVersion || this.confirmNewFinal(item)) {
                this.$store.dispatch(
                    'applications/markDocumentVersionFinal',
                    {
                        application: this.application, 
                        document: item
                    }
                );
            }

        }
    }
}
</script>