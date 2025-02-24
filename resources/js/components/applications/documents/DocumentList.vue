<template>
    <div>
        <data-table 
            :fields="filteredFields" 
            :data="filteredDocuments" 
            :sort="{field: filteredFields[0].name, desc: true}"
            v-if="filteredDocuments.length > 0"
        >

            <template v-slot:cell-notes="{value}">
                <truncate-expander :value="value" :truncate-length="50"></truncate-expander>
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
                <button class="btn btn-xs" @click="openEditForm(item)">
                    <icon-edit width="12" height="16"></icon-edit>
                </button>
                <TrashButton @click="initDelete(item)"></TrashButton>
                </div>
            </template>
        </data-table>

        <div v-else class="px-2 py-1 border bg-gray-100 rounded">No documents uploaded</div>

        <modal-dialog v-model="showEditForm">
            <DocumentEditForm
                :document="activeDocument"
                :application="application"
                @canceled="showEditForm = false"
                @saved="handleDocumentEdited"
                @triggermarkreviewed="showMarkReviewed"
            />
        </modal-dialog>

        <modal-dialog 
            v-model="showDeleteConfirmation" 
            :title="`You are about to delete ${activeDocument.type.long_name}, v${activeDocument.version}`"
        >
            <div v-if="activeDocument">
                <p v-if="activeDocument.is_final" class="mb-3">
                    This version has been tagged as the final version of the document.
                </p>
                <p>Are you sure you want to continue?</p>

                <button-row 
                    submit-text="Delete Document" 
                    @canceled="cancelDelete" 
                    @submitted="commitDelete"
                >
                </button-row>
            </div>
        </modal-dialog>

    </div>
</template>
<script>



import TrashButton from '@/components/buttons/TrashIconButton.vue';
import DocumentEditForm from './DocumentEditForm';
import is_validation_error from '../../../http/is_validation_error';

export default {
    components: {
        DocumentEditForm,
        TrashButton,
    },
    props: {
        documentTypeId: {
            type: Number,
            required: true
        },
        getsReviewed: {
            type: Boolean,
            required: false,
            default: true
        },
        showVersion: {
            type: Boolean,
            required: false,
            default: true
        }
    },
    data() {
        return {
            showDeleteConfirmation: false,
            showReviewedForm: false,
            showEditForm: false,
            activeDocument: {type: {}},
            fields: [
                {
                    name: 'version',
                    label: 'Version',
                    type: Number
                },
                {
                    name: 'filename',
                    label: 'File',
                    type: String,
                    headerClass: ['w-1/3']
                },
                {
                    name: 'notes',
                    label: 'Notes',
                    type: String,
                    sortable: false,
                    resolveValue (item) {
                        return item.notes || '';
                    },
                    headerClass: ['w-1/3']
                },
                {
                    name: 'date_received',
                    label: 'Date Received',
                    type: Date,
                    headerClass: ['w-32']
                },
                {
                    name: 'is_final',
                    label: 'Final',
                    type: Boolean,
                    sortable: false,
                    class: ['text-center'],
                    headerClass: ['text-center', 'w-12']
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
        group () {
            return this.$store.getters['groups/currentItemOrNew']
        },
        application () {
            return this.group.expert_panel
        },
        
        filteredFields () {
            const clonedFields = [...this.fields]
            if (!this.showVersion) {
                const kdx = clonedFields.findIndex(f => f && f.name === 'version')
                clonedFields.splice(kdx, 1)

                const jdx = clonedFields.findIndex(f => f && f.name === 'is_final')
                clonedFields.splice(jdx, 1)
            }

            if (!this.getsReviewed) {
                const idx = clonedFields.findIndex(f => f && f.name === 'date_reviewed')
                clonedFields.splice(idx, 1)
            }
            return clonedFields
        },
        filteredDocuments() {
            if (this.application.documents) {
                return this.application.documents.filter(d =>  d.document_type_id === this.documentTypeId)
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
                // eslint-disable-next-line no-alert
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
        },
        openEditForm(item) {
            this.showEditForm = true; 
            this.activeDocument = item;
        },
        initDelete(item) {
            this.activeDocument = item;
            this.showDeleteConfirmation = true;
        },
        cancelDelete() {
            this.showDeleteConfirmation = false;
        },
        handleDocumentEdited() {
            this.showEditForm = false; 
            this.$emit('updated')
        },
        async commitDelete() {
            try {
                await this.$store.dispatch('applications/deleteDocument', {application: this.application, document: this.activeDocument});
                this.showDeleteConfirmation = false;
                this.$emit('updated');
            } catch (err) {
                if (is_validation_error(err)) {
                    // eslint-disable-next-line no-alert
                    alert(err);
                }
            }
        }
    }
}
</script>